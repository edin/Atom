<?php

namespace Atom\Interfaces;

interface IViewEngine {
    public function render(string $viewPath, array $params = []): string;
}