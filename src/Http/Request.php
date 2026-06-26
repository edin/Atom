<?php

declare(strict_types=1);

namespace Atom\Http;

final readonly class Request
{
    private ParameterCollection $query;
    private ParameterCollection $post;
    private FileCollection $files;
    private ParameterCollection $server;
    private HeaderCollection $headers;

    /**
     * @param array<string, mixed> $queryParams
     * @param array<string, mixed> $parsedBody
     * @param array<string, mixed> $serverParams
     * @param array<string, mixed> $files
     * @param array<string, string|string[]|int|float|bool|null> $headers
     */
    public function __construct(
        private string $method,
        private string $path,
        array $queryParams = [],
        array $parsedBody = [],
        private string $body = "",
        array $serverParams = [],
        array $files = [],
        array $headers = []
    ) {
        $this->query = new ParameterCollection($queryParams);
        $this->post = new ParameterCollection($parsedBody);
        $this->server = new ParameterCollection($serverParams);
        $this->files = new FileCollection($files);
        $this->headers = $headers === [] ? HeaderCollection::fromServer($serverParams) : new HeaderCollection($headers);
    }

    public static function fromGlobals(): self
    {
        $path = parse_url($_SERVER["REQUEST_URI"] ?? "/", PHP_URL_PATH) ?: "/";

        return new self(
            $_SERVER["REQUEST_METHOD"] ?? "GET",
            $path,
            $_GET,
            $_POST,
            file_get_contents("php://input") ?: "",
            $_SERVER,
            $_FILES
        );
    }

    public function getMethod(): string
    {
        return strtoupper($this->method);
    }

    public function getPath(): string
    {
        return $this->path === "" ? "/" : $this->path;
    }

    public function query(): ParameterCollection
    {
        return $this->query;
    }

    public function post(): ParameterCollection
    {
        return $this->post;
    }

    public function files(): FileCollection
    {
        return $this->files;
    }

    public function server(): ParameterCollection
    {
        return $this->server;
    }

    public function headers(): HeaderCollection
    {
        return $this->headers;
    }

    /**
     * @return array<string, mixed>
     */
    public function getQueryParams(): array
    {
        return $this->query->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function getParsedBody(): array
    {
        return $this->post->toArray();
    }

    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @return array<string, mixed>
     */
    public function getServerParams(): array
    {
        return $this->server->toArray();
    }
}
