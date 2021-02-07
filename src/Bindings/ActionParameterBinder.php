<?php

namespace Atom\Bindings;

final class ActionParameterBinder implements ActionParameterBinderInterface
{
    private ModelBinderInterface $modelBinder;

    public function getModelBinder(): ModelBinderInterface
    {
        return $this->modelBinder;
    }

    public function bindActionParams($action, $params)
    {
        $method = $action->getMethod();
        $binder = $this->getModelBinder();
        $bindingContext = new BindingContext($this->request, $binder, $action, $params);

        $arguments = [];
        $missing = [];
        $methodParameters = [];

        foreach ($method->getParameters() as $param) {
            $name = $param->getName();
            $value = $bindingContext->getParameterValue($name);
            $methodParameters[$name] = new BindingParameter($param, $value);
        }

        foreach ($methodParameters as $name => $param) {
            $result = $binder->bindModel($param, $bindingContext);
            if ($result instanceof BindingResult) {
                $arguments[$name] = $result->value;
            } else {
                $arguments[$name] = null;
                $missing[] = $name;
            }
        }

        $result = new ActionBindingResult;
        $result->parameters = $methodParameters;
        $result->arguments = $arguments;
        $result->missing = $missing;
        return $result;
    }
}
