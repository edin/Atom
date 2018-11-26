<?php

interface ViewEngineInterface {
    public function render(string $viewPath, array $model): string;
}