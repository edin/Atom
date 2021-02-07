<?php

namespace Atom\Bindings;

interface ActionParameterBinderInterface
{
    /**
     * @param $action
     * @param array $params
     * @return ActionBindingResult
     */
    public function bindActionParams($action, $params);
}
