<?php

declare(strict_types=1);

namespace Atom\Logging;

use DateTimeImmutable;
use JsonException;
use RuntimeException;
use Throwable;

final readonly class FileLogger implements Logger
{
    public function __construct(private string $path)
    {
    }

    public function info(string $message, array $context = []): void
    {
        $this->write("info", $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->write("error", $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function write(string $level, string $message, array $context): void
    {
        $directory = dirname($this->path);
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new RuntimeException("Cannot create log directory '{$directory}'.");
        }

        $line = sprintf(
            "[%s] %s: %s%s%s",
            (new DateTimeImmutable())->format("Y-m-d H:i:s"),
            strtoupper($level),
            $message,
            $context === [] ? "" : " " . $this->encodeContext($context),
            PHP_EOL
        );

        if (file_put_contents($this->path, $line, FILE_APPEND | LOCK_EX) === false) {
            throw new RuntimeException("Cannot write log file '{$this->path}'.");
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    private function encodeContext(array $context): string
    {
        try {
            return json_encode($this->normalize($context), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        } catch (JsonException) {
            return '{"context":"unserializable"}';
        }
    }

    private function normalize(mixed $value): mixed
    {
        if ($value instanceof Throwable) {
            return [
                "type" => $value::class,
                "message" => $value->getMessage(),
                "file" => $value->getFile(),
                "line" => $value->getLine(),
            ];
        }

        if (is_array($value)) {
            return array_map(fn(mixed $item): mixed => $this->normalize($item), $value);
        }

        if (is_object($value)) {
            return $value::class;
        }

        return $value;
    }
}
