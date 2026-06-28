<?php

declare(strict_types=1);

namespace Atom\Http;

use Atom\Router\MatchedRoute;
use RuntimeException;

final readonly class StaticFileHandler
{
    public function serve(MatchedRoute $route, Response $response): Response
    {
        $metadata = $route->getRouteEntry()->getMetadataOfType(StaticFileRouteMetadata::class);
        if (!$metadata instanceof StaticFileRouteMetadata) {
            throw new RuntimeException("Static file route metadata is missing.");
        }

        $file = $this->resolveFile($metadata->directory, $route->getRouteParams()["path"] ?? "");
        if ($file === null) {
            return $response->status(404)->content("Not Found");
        }

        $contents = file_get_contents($file);
        if ($contents === false) {
            return $response->status(404)->content("Not Found");
        }

        return $response
            ->header("Content-Type", $this->contentType($file))
            ->content($contents);
    }

    private function resolveFile(string $directory, string $path): ?string
    {
        $root = realpath($directory);
        if ($root === false) {
            return null;
        }

        $path = str_replace("\\", "/", rawurldecode($path));
        $path = ltrim($path, "/");

        if ($path === "" || str_contains($path, "../")) {
            return null;
        }

        $file = realpath($root . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $path));
        if ($file === false || !is_file($file)) {
            return null;
        }

        return str_starts_with($file, $root . DIRECTORY_SEPARATOR) ? $file : null;
    }

    private function contentType(string $file): string
    {
        return match (strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
            "css" => "text/css; charset=utf-8",
            "js" => "application/javascript; charset=utf-8",
            "json" => "application/json; charset=utf-8",
            "svg" => "image/svg+xml",
            "png" => "image/png",
            "jpg", "jpeg" => "image/jpeg",
            "gif" => "image/gif",
            "webp" => "image/webp",
            "ico" => "image/x-icon",
            default => "application/octet-stream",
        };
    }
}
