<?php

declare(strict_types=1);

namespace Atom\Dispatcher\ResultHandler;

use Atom\Container\Container;

trait ResultHandlerTrait
{
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    protected function getContainer()
    {
        return $this->container;
    }

    protected function getResponse()
    {
        return $this->container->Response;
    }
}
