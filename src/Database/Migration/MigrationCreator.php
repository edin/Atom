<?php

declare(strict_types=1);

namespace Atom\Database\Migration;

use Atom\Console\FileTemplateRenderer;
use RuntimeException;

final readonly class MigrationCreator
{
    public function __construct(
        private MigrationOptions $options,
        private FileTemplateRenderer $templates = new FileTemplateRenderer()
    ) {
    }

    public function create(string $name): string
    {
        if (!$this->options->hasSource()) {
            throw new RuntimeException("Migration directory is not configured.");
        }

        $directory = rtrim($this->options->directory, "\\/");
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new RuntimeException("Unable to create migration directory '{$directory}'.");
        }

        $file = $directory . DIRECTORY_SEPARATOR . $this->fileName($name) . ".php";
        if (file_exists($file)) {
            throw new RuntimeException("Migration file '{$file}' already exists.");
        }

        file_put_contents($file, $this->templates->render("database/migration.php.tpl"));

        return $file;
    }

    private function fileName(string $name): string
    {
        return "M" . date("y_m_d_His") . "_" . $this->normalize($name);
    }

    private function normalize(string $name): string
    {
        $name = preg_replace('/[^A-Za-z0-9]+/', "_", trim($name)) ?? "";
        $name = strtolower(trim($name, "_"));

        if ($name === "") {
            throw new RuntimeException("Migration name cannot be empty.");
        }

        return $name;
    }

}
