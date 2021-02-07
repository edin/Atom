<?php

namespace Atom\Bindings\Binders;

use Atom\Bindings\BindingContext;
use Atom\Bindings\BindingProperty;
use Atom\Bindings\BindingResult;
use Atom\Bindings\BindingTargetInterface;
use Atom\Bindings\ModelBinderInterface;
use Exception;
use ReflectionClass;

final class ClassTypeBinder implements ModelBinderInterface
{
    protected function getParams($target, $context)
    {
        if ($context->request->getIsGet()) {
            $params = $target->getValue();
            if (!is_array($params)) {
                $params = json_decode($params, true);
            }
        } else {
            $params = $context->request->getBodyParams();
        }
        return $params;
    }

    public function bindModel(BindingTargetInterface $target, BindingContext $context): ?BindingResult
    {
        $typeName = $target->getTypeName();
        if ($typeName === null) {
            return null;
        }

        $data = $this->getParams($target, $context);
        
        //TODO: Use container to create object
        $instance = new $typeName;

        $result = $this->hydrateObject($instance, $data, $context);

        return new BindingResult($result);
    }

    protected function hydrateObject($instance, $data, $context)
    {
        $reflection = new ReflectionClass($instance);

        foreach ($reflection->getProperties() as $prop) {
            try
            {
                $value = null;
                if (isset($data[$prop->name])) {
                    $value = $data[$prop->name];
                }

                $bindingParameter = new BindingProperty($prop, $value);
                $result = $context->binder->bindModel($bindingParameter, $context);

                if ($result instanceof BindingResult) {
                    $prop->setAccessible(true);
                    $prop->setValue($instance, $result->value);
                }
            } catch (Exception $e) {  }
        }
        return $instance;
    }
}
