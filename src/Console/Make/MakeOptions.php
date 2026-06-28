<?php

declare(strict_types=1);

namespace Atom\Console\Make;

final readonly class MakeOptions
{
    public function __construct(
        public string $root = "",
        public string $templateDirectory = "templates/atom",
        public string $pageDirectory = "app/Pages",
        public string $pageNamespace = "App\\Pages",
        public string $componentDirectory = "app/Components",
        public string $componentNamespace = "App\\Components"
    ) {
    }
}
