<?php

declare(strict_types=1);

namespace Atom\Console\Make;

use Atom\Console\FileTemplateRenderer;
use RuntimeException;

final readonly class ApplicationFileCreator
{
    public function __construct(
        private FileTemplateRenderer $templates,
        private MakeOptions $options = new MakeOptions()
    ) {
    }

    /**
     * @return array{class: string, view: string}
     */
    public function page(string $name, string $path = ""): array
    {
        $className = $this->className($name, "Page");
        $routePath = $path !== "" ? $path : "/" . $this->routePath($className, "Page");
        $directory = $this->directory($this->options->pageDirectory);

        $classFile = $directory . DIRECTORY_SEPARATOR . $className . ".php";
        $viewFile = $directory . DIRECTORY_SEPARATOR . $className . ".atom.html";

        $this->write($classFile, $this->templates->render("page/page.php.tpl", [
            "namespace" => $this->options->pageNamespace,
            "class" => $className,
            "route" => $routePath,
            "title" => $this->title($className, "Page"),
        ]));
        $this->write($viewFile, $this->templates->render("page/page.atom.html.tpl", [
            "title" => $this->title($className, "Page"),
        ]));

        return ["class" => $classFile, "view" => $viewFile];
    }

    public function component(string $name): string
    {
        $className = $this->className($name);
        $directory = $this->directory($this->options->componentDirectory);
        $file = $directory . DIRECTORY_SEPARATOR . $className . ".php";

        $this->write($file, $this->templates->render("component/component.php.tpl", [
            "namespace" => $this->options->componentNamespace,
            "class" => $className,
        ]));

        return $file;
    }

    private function className(string $name, string $suffix = ""): string
    {
        $parts = preg_split('/[^A-Za-z0-9]+/', trim($name)) ?: [];
        $class = "";

        foreach ($parts as $part) {
            if ($part === "") {
                continue;
            }

            $class .= ucfirst($part);
        }

        if ($class === "") {
            throw new RuntimeException("Name cannot be empty.");
        }

        if ($suffix !== "" && !str_ends_with($class, $suffix)) {
            $class .= $suffix;
        }

        return $class;
    }

    private function routePath(string $className, string $suffix): string
    {
        $name = str_ends_with($className, $suffix)
            ? substr($className, 0, -strlen($suffix))
            : $className;

        $path = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', $name));

        return trim($path, "-");
    }

    private function title(string $className, string $suffix = ""): string
    {
        $name = $suffix !== "" && str_ends_with($className, $suffix)
            ? substr($className, 0, -strlen($suffix))
            : $className;

        return trim((string) preg_replace('/(?<!^)[A-Z]/', ' $0', $name));
    }

    private function directory(string $directory): string
    {
        $path = $this->path($directory);
        if (!is_dir($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
            throw new RuntimeException("Unable to create directory '{$path}'.");
        }

        return $path;
    }

    private function write(string $file, string $contents): void
    {
        if (file_exists($file)) {
            throw new RuntimeException("File '{$file}' already exists.");
        }

        file_put_contents($file, $contents);
    }

    private function path(string $path): string
    {
        $path = str_replace(["/", "\\"], DIRECTORY_SEPARATOR, $path);

        if ($this->options->root === "" || preg_match('/^(?:[A-Za-z]:[\/\\\\]|[\/\\\\])/', $path) === 1) {
            return $path;
        }

        return rtrim($this->options->root, "/\\") . DIRECTORY_SEPARATOR . ltrim($path, "/\\");
    }
}
