<?php

declare(strict_types=1);

namespace Atom\Database\Lock;

final readonly class FileDatabaseLockManager implements DatabaseLockManagerInterface
{
    public function __construct(private string $directory)
    {
    }

    public function acquire(string $name): ?DatabaseLock
    {
        if (!is_dir($this->directory) && !mkdir($this->directory, 0777, true) && !is_dir($this->directory)) {
            return null;
        }

        $handle = fopen($this->directory . DIRECTORY_SEPARATOR . $this->fileName($name), "c+");
        if ($handle === false || !flock($handle, LOCK_EX | LOCK_NB)) {
            if (is_resource($handle)) {
                fclose($handle);
            }

            return null;
        }

        return new DatabaseLock(static function () use ($handle): void {
            flock($handle, LOCK_UN);
            fclose($handle);
        });
    }

    private function fileName(string $name): string
    {
        return preg_replace('/[^A-Za-z0-9_.-]+/', "_", $name) . ".lock";
    }
}
