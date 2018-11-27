<?php

namespace Atom\Interfaces;

interface IViewInfo {
    public function getViewName(): string;
    public function getModel();
}