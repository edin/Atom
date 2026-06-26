<?php

declare(strict_types=1);

namespace Atom\Database\Seeder;

use RuntimeException;

final readonly class SeederCreator
{
    public function __construct(private SeederOptions $options)
    {
    }

    public function create(string $name): string
    {
        if (!$this->options->hasSource()) {
            throw new RuntimeException("Seeder directory is not configured.");
        }

        $directory = rtrim($this->options->directory, "\\/");
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new RuntimeException("Unable to create seeder directory '{$directory}'.");
        }

        $file = $directory . DIRECTORY_SEPARATOR . $this->fileName($name) . ".php";
        if (file_exists($file)) {
            throw new RuntimeException("Seeder file '{$file}' already exists.");
        }

        file_put_contents($file, $this->template());

        return $file;
    }

    private function fileName(string $name): string
    {
        return "S" . date("y_m_d_His") . "_" . $this->normalize($name);
    }

    private function normalize(string $name): string
    {
        $name = preg_replace('/[^A-Za-z0-9]+/', "_", trim($name)) ?? "";
        $name = strtolower(trim($name, "_"));

        if ($name === "") {
            throw new RuntimeException("Seeder name cannot be empty.");
        }

        return $name;
    }

    private function template(): string
    {
        return <<<'PHP'
<?php

use Atom\Database\Seeder\Seeder;

return new class extends Seeder
{
    public function run(): void
    {
    }
};

PHP;
    }
}
