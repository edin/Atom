<?php

declare(strict_types=1);

namespace Atom\Page;

use Atom\Di\InjectionContext;
use Atom\Di\Injector;
use Atom\Http\Request;
use Atom\Profiler\Profile;
use Atom\Router\MatchedRoute;
use Atom\View\Ast\AttributeNode;
use Atom\View\Ast\ElementNode;
use Atom\View\Ast\ExpressionNode;
use Atom\View\Ast\RawTextNode;
use Atom\View\Ast\TemplateNode;
use Atom\View\Html;
use Atom\View\TemplateCache;
use Atom\View\Parser\ViewParser;
use Atom\View\Render\ViewRenderer;
use ReflectionClass;

final readonly class PageRenderer
{
    public function __construct(
        private Injector $injector,
        private InjectionContext $context,
        private PageViewLocator $viewLocator = new PageViewLocator(),
        private ViewParser $parser = new ViewParser(),
        private TemplateCache $templates = new TemplateCache(),
        private ViewRenderer $renderer = new ViewRenderer(),
        private PageStateSerializerInterface $state = new JsonPageStateSerializer()
    ) {
    }

    /**
     * @param class-string<Page> $pageClass
     */
    public function render(string $pageClass): mixed
    {
        return Profile::measure("page.render", function () use ($pageClass): mixed {
            $page = $this->injector->instantiate($pageClass, context: $this->context);
            $this->restoreState($page);

            return $this->renderPage($page);
        }, ["page" => $pageClass]);
    }

    public function renderPage(Page $page, bool $invokeGet = true): mixed
    {
        $result = $invokeGet
            ? Profile::measure("page.invoke.get", fn(): mixed => $this->invokeGet($page), ["page" => $page::class])
            : null;

        if ($result !== null) {
            return $result;
        }

        $variables = [
            "this" => $page,
            "page" => $page,
            "currentPath" => $this->currentPath(),
        ];

        $templatePath = $this->viewLocator->locate($page);
        $template = $this->templates->remember(
            $templatePath,
            fn(): TemplateNode => Profile::measure(
                "view.parse",
                fn(): TemplateNode => $this->parser->parse(file_get_contents($templatePath) ?: ""),
                ["page" => $page::class, "template" => $templatePath]
            )
        );
        $content = Profile::measure(
            "view.render",
            fn(): string => $this->renderer->render($template, $variables),
            ["page" => $page::class, "template" => $templatePath]
        );

        $html = Profile::measure(
            "page.layout",
            fn(): string => $this->renderLayout($page, $content, $variables),
            ["page" => $page::class]
        );
        $state = Profile::measure("page.state", fn(): string => $this->state->serialize($page), ["page" => $page::class]);

        return $this->injectState($html, $state);
    }

    /**
     * @param array<string, mixed> $variables
     */
    private function renderLayout(Page $page, string $content, array $variables): string
    {
        $layout = $page->layout;
        if ($layout === null) {
            return $content;
        }

        return $this->renderer->render(new TemplateNode([
            new ElementNode($layout, [
                new AttributeNode("page", new ExpressionNode('$page')),
            ], [
                new RawTextNode($content),
            ]),
        ]), $variables);
    }

    private function invokeGet(Page $page): mixed
    {
        $reflection = new ReflectionClass($page);
        if (!$reflection->hasMethod("get")) {
            return null;
        }

        return $this->injector->invoke([$page, "get"], $this->routeParams(), $this->context);
    }

    /**
     * @return array<string, mixed>
     */
    private function routeParams(): array
    {
        $route = $this->context->get(MatchedRoute::class);

        return $route instanceof MatchedRoute ? $route->getRouteParams() : [];
    }

    private function currentPath(): string
    {
        $request = $this->context->get(Request::class);

        return $request instanceof Request ? $request->getPath() : "";
    }

    private function restoreState(Page $page): void
    {
        $request = $this->context->get(Request::class);
        if (!$request instanceof Request) {
            return;
        }

        $state = $request->post()->string("_state", $request->query()->string("_state"));
        $this->state->deserialize($page, $state);
    }

    private function injectState(string $html, string $state): string
    {
        if ($state === "") {
            return $html;
        }

        $meta = '<meta name="atom-state" content="' . Html::escape($state) . '">';

        if (str_contains($html, 'name="atom-state"')) {
            return preg_replace('/<meta\s+name="atom-state"\s+content="[^"]*"\s*\/?>/i', $meta, $html, 1) ?? $html;
        }

        if (stripos($html, "</head>") !== false) {
            return preg_replace('/<\/head>/i', "    {$meta}\n</head>", $html, 1) ?? $html;
        }

        return $meta . $html;
    }
}
