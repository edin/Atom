<?php

declare(strict_types=1);

namespace Atom\Console\Commands;

use Atom\Console\Attributes\ConsoleCommand;
use Atom\Console\ConsoleOutput;
use Atom\Console\Make\ApplicationFileCreator;
use Atom\Console\Make\MakeOptions;

final readonly class MakeCommands
{
    public function __construct(
        private ApplicationFileCreator $creator,
        private MakeOptions $options,
        private ConsoleOutput $output
    ) {
    }

    #[ConsoleCommand("make:page", "Create a page class and template")]
    public function page(string $name, string $path = ""): int
    {
        $files = $this->creator->page($name, $path);

        $this->output->line("Created page: " . $this->output->command($this->displayPath($files["class"])));
        $this->output->line("Created view: " . $this->output->command($this->displayPath($files["view"])));

        return 0;
    }

    #[ConsoleCommand("make:component", "Create a component class")]
    public function component(string $name): int
    {
        $file = $this->creator->component($name);

        $this->output->line("Created component: " . $this->output->command($this->displayPath($file)));

        return 0;
    }

    private function displayPath(string $path): string
    {
        $path = str_replace(["/", "\\"], DIRECTORY_SEPARATOR, $path);
        $root = str_replace(["/", "\\"], DIRECTORY_SEPARATOR, $this->options->root);

        if ($root !== "") {
            $root = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

            if (str_starts_with($path, $root)) {
                return str_replace(DIRECTORY_SEPARATOR, "/", substr($path, strlen($root)));
            }
        }

        return str_replace(DIRECTORY_SEPARATOR, "/", $path);
    }
}
