<?php

declare(strict_types=1);

namespace Atom\Console;

use Atom\Di\InjectionContext;
use Atom\Di\Injector;
use Atom\Di\Provider;
use ReflectionMethod;

final readonly class MethodCommand implements CommandInterface
{
    /**
     * @param class-string $className
     */
    public function __construct(
        private Injector $injector,
        private string $className,
        private string $methodName
    ) {
    }

    public function handle(ConsoleInput $input, ConsoleOutput $output): int
    {
        $commandInjector = $this->injector->createChild([
            Provider::value(ConsoleInput::class, $input),
            Provider::value(ConsoleOutput::class, $output),
        ]);
        $context = new InjectionContext();
        $context->set(ConsoleInput::class, $input);
        $context->set(ConsoleOutput::class, $output);

        $target = $commandInjector->get($this->className, $context);
        $method = new ReflectionMethod($this->className, $this->methodName);
        $parameters = (new ConsoleParameterBinder($commandInjector, $context, $input, $output))
            ->bindNamed($method);
        $result = $commandInjector->invoke([$target, $this->methodName], $parameters, $context);

        if (is_string($result)) {
            $output->write($result);
        }

        return is_int($result) ? $result : 0;
    }
}
