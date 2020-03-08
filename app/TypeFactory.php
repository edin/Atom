<?php

namespace App;

use Atom\Container\Container;
use Atom\Container\TypeFactory\TypeFactoryRegistry;
use Atom\Container\TypeInfo;
use Atom\Dispatcher\RequestTypeFactory;

class TypeFactory
{
    public function configureServices(Container $container)
    {
        $container->bind(TypeFactoryRegistry::class)->toFactory(function () {
            $registry = new TypeFactoryRegistry();

            $registry->registerFactory(RequestTypeFactory::class, function (TypeInfo $type) {
                return $type->inNamespace("App\Messages");
            });

            return $registry;
        })->withName("TypeFactory");
    }
}
