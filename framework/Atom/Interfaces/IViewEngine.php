<?php

namespace Atom\Interfaces;

interface IViewEngine
{
    public function setParams(array $params): void;
    public function render(string $viewName, array $params = []): string;
}
