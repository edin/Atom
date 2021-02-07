<?php

namespace Atom\Bindings;

final class BindingContext
{
    /**
     * @var Request
     */
    public $request;

    /**
     * @var ModelBinderInterface
     */
    public $binder;

    /**
     * @var Action
     */
    public $action;

    /**
     * @var array $params
     */
    public $params;

    public function __construct($request, $binder, $action, $params)
    {
        $this->request = $request;
        $this->binder = $binder;
        $this->action = $action;
        $this->params = $params;
    }

    public function getParameterValue($name)
    {
        if (isset($this->params[$name])) {
            return $this->params[$name];
        }
        return null;
    }
}
