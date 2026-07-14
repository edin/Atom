<?php

declare(strict_types=1);

namespace Atom\Modules\DevReload;

use Atom\Support\Paths;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final readonly class DevReloadWatcher
{
    /**
     * @param string[] $extensions
     * @param string[] $ignoredDirectories
     */
    public function __construct(
        private Paths $paths,
        private array $extensions = ["php", "atom.html", "atom.php", "html", "css", "js"],
        private array $ignoredDirectories = [".git", "vendor", "node_modules", "var", "storage"]
    ) {
    }

    /**
     * @param string[] $watchPaths
     */
    public function version(array $watchPaths): string
    {
        $latest = 0;
        $count = 0;

        foreach ($watchPaths as $path) {
            foreach ($this->files($this->paths->resolve($path)) as $file) {
                $latest = max($latest, $file->getMTime());
                $count++;
            }
        }

        return sha1($latest . ":" . $count);
    }

    /**
     * @return iterable<SplFileInfo>
     */
    private function files(string $path): iterable
    {
        if (is_file($path)) {
            $file = new SplFileInfo($path);
            if ($this->shouldWatch($file)) {
                yield $file;
            }

            return;
        }

        if (!is_dir($path)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile()) {
                continue;
            }

            if ($this->isIgnored($file) || !$this->shouldWatch($file)) {
                continue;
            }

            yield $file;
        }
    }

    private function shouldWatch(SplFileInfo $file): bool
    {
        $name = strtolower($file->getFilename());

        foreach ($this->extensions as $extension) {
            if (str_ends_with($name, "." . strtolower($extension))) {
                return true;
            }
        }

        return false;
    }

    private function isIgnored(SplFileInfo $file): bool
    {
        $path = str_replace("\\", "/", $file->getPathname());

        foreach ($this->ignoredDirectories as $directory) {
            if (str_contains($path, "/" . trim($directory, "/") . "/")) {
                return true;
            }
        }

        return false;
    }
}
