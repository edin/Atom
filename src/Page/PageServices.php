<?php

declare(strict_types=1);

namespace Atom\Page;

use Atom\Di\Bindings;
use Atom\Di\ServiceProviderInterface;
use Atom\View\Component\ComponentFactoryInterface;
use Atom\View\Component\ComponentHydrator;
use Atom\View\Component\ComponentRegistry;
use Atom\View\Component\InjectorComponentFactory;
use Atom\View\TemplateCache;
use Atom\View\Parser\ViewParser;
use Atom\View\Render\ExpressionEvaluatorInterface;
use Atom\View\Render\PhpExpressionEvaluator;
use Atom\View\Render\ViewRenderer;

final readonly class PageServices implements ServiceProviderInterface
{
    public function register(Bindings $bindings): void
    {
        $bindings->bind(ExpressionEvaluatorInterface::class)
            ->to(PhpExpressionEvaluator::class)
            ->singleton();

        $bindings->bind(ComponentRegistry::class)
            ->toSelf()
            ->singleton();

        $bindings->bind(ComponentFactoryInterface::class)
            ->toFactory(fn($injector, $context) => new InjectorComponentFactory($injector, $context))
            ->scoped();

        $bindings->bind(ComponentHydrator::class)
            ->toSelf()
            ->scoped();

        $bindings->bind(ViewParser::class)
            ->toSelf()
            ->singleton();

        $bindings->bind(TemplateCache::class)
            ->toSelf()
            ->singleton();

        $bindings->bind(ViewRenderer::class)
            ->toSelf()
            ->scoped();

        $bindings->bind(PageViewLocator::class)
            ->toSelf()
            ->singleton();

        $bindings->bind(PageRenderer::class)
            ->toSelf()
            ->scoped();

        $bindings->bind(PageActionHandler::class)
            ->toSelf()
            ->scoped();

        $bindings->bind(PageInputHydratorInterface::class)
            ->to(PageInputHydrator::class)
            ->scoped();

        $bindings->bind(PageStateSerializer::class)
            ->to(JsonPageStateSerializer::class)
            ->scoped();
    }
}
