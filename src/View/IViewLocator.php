<?php

namespace Atom\View;

interface IViewLocator
{
    public function getViewDirectory(): string;
    public function getView($model): string;
}
