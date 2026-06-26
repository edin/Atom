<?php

declare(strict_types=1);

namespace Atom\Page;

use Atom\Di\InjectionContext;
use Atom\Di\Injector;
use Atom\Http\Request;
use Atom\Router\MatchedRoute;
use Atom\View\Ast\AttributeNode;
use Atom\View\Ast\ElementNode;
use Atom\View\Ast\ExpressionNode;
use Atom\View\Ast\RawTextNode;
use Atom\View\Ast\TemplateNode;
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
        private ViewRenderer $renderer = new ViewRenderer()
    ) {
    }

    /**
     * @param class-string<Page> $pageClass
     */
    public function render(string $pageClass): mixed
    {
        $page = $this->injector->instantiate($pageClass, context: $this->context);
        $result = $this->invokeGet($page);

        if ($result !== null) {
            return $result;
        }

        $variables = [
            "this" => $page,
            "page" => $page,
        ];

        $template = $this->parser->parse(file_get_contents($this->viewLocator->locate($pageClass)) ?: "");
        $content = $this->renderer->render($template, $variables);

        return $this->renderLayout($page, $content, $variables);
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
        $method = $this->pageMethod();
        if (!$reflection->hasMethod($method)) {
            return null;
        }

        return $this->injector->invoke([$page, $method], $this->routeParams(), $this->context);
    }

    private function pageMethod(): string
    {
        $request = $this->context->get(Request::class);

        return $request instanceof Request ? strtolower($request->getMethod()) : "get";
    }

    /**
     * @return array<string, mixed>
     */
    private function routeParams(): array
    {
        $route = $this->context->get(MatchedRoute::class);

        return $route instanceof MatchedRoute ? $route->getRouteParams() : [];
    }
}
