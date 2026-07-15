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
    private CookieCollection $cookies;

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
        $this->cookies = CookieCollection::fromHeader($this->headers->get("Cookie", "") ?? "");
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

    public function withMethod(string $method): self
    {
        return new self(
            $method,
            $this->path,
            $this->query->toArray(),
            $this->post->toArray(),
            $this->body,
            $this->server->toArray(),
            $this->files->toArray(),
            $this->headers->toArray()
        );
    }

    /**
     * @param array<string, mixed> $serverParams
     * @param array<string, string|string[]|int|float|bool|null>|null $headers
     */
    public function withServerParams(array $serverParams, ?array $headers = null): self
    {
        return new self(
            $this->method,
            $this->path,
            $this->query->toArray(),
            $this->post->toArray(),
            $this->body,
            $serverParams,
            $this->files->toArray(),
            $headers ?? $this->headers->toArray()
        );
    }

    public function withHeader(string $name, string $value): self
    {
        $headers = new HeaderCollection($this->headers->toArray());
        $headers->set($name, $value);

        return $this->withServerParams($this->server->toArray(), $headers->toArray());
    }

    public function getPath(): string
    {
        return $this->path === "" ? "/" : $this->path;
    }

    public function isSecure(): bool
    {
        return $this->getScheme() === "https";
    }

    public function getScheme(): string
    {
        return in_array(strtolower($this->server->string("HTTPS")), ["1", "on", "true"], true)
            || $this->server->int("SERVER_PORT") === 443
            ? "https"
            : "http";
    }

    public function getHost(): string
    {
        return $this->server->string("HTTP_HOST", $this->headers->get("Host", "") ?? "");
    }

    public function getClientIp(): string
    {
        return $this->server->string("REMOTE_ADDR");
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

    public function cookies(): CookieCollection
    {
        return $this->cookies;
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
