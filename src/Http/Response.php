<?php

declare(strict_types=1);

namespace Atom\Http;

class Response
{
    private int $status = 200;
    private HeaderCollection $headers;
    private ResponseBodyInterface $body;

    public function __construct(private ?CookieJar $cookieJar = null)
    {
        $this->headers = new HeaderCollection();
        $this->body = new ContentStream();
    }

    public function cookie(Cookie $cookie): self
    {
        if ($this->cookieJar !== null) {
            $this->cookieJar->set($cookie);
        } else {
            $this->addHeader("Set-Cookie", $cookie->toHeader());
        }

        return $this;
    }

    public function removeCookie(string $name, string $path = "/", string $domain = ""): self
    {
        return $this->cookie(Cookie::forget($name, $path, $domain));
    }

    public function status(int $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getReasonPhrase(): string
    {
        return match ($this->status) {
            200 => "OK",
            201 => "Created",
            204 => "No Content",
            301 => "Moved Permanently",
            302 => "Found",
            400 => "Bad Request",
            401 => "Unauthorized",
            403 => "Forbidden",
            404 => "Not Found",
            405 => "Method Not Allowed",
            413 => "Content Too Large",
            429 => "Too Many Requests",
            500 => "Internal Server Error",
            default => "",
        };
    }

    public function redirect(string $location, int $status = 302): self
    {
        $this->header("Location", $location);
        $this->status($status);
        return $this;
    }

    public function json(mixed $data): self
    {
        $this->header("Content-Type", "application/json");
        $this->body = new ContentStream((string) json_encode($data));
        return $this;
    }

    public function header(string $name, string|int|float|bool|null $value): self
    {
        if ($value === null || $value === "") {
            $this->headers->remove($name);
        } else {
            $this->headers->set($name, $value);
        }
        return $this;
    }

    public function addHeader(string $name, string|int|float|bool $value): self
    {
        $this->headers->add($name, $value);
        return $this;
    }

    /**
     * @param array<string, string|int|float|bool|null> $headers
     */
    public function withHeaders(array $headers): self
    {
        foreach ($headers as $name => $value) {
            $this->header($name, $value);
        }
        return $this;
    }

    /**
     * @return array<string, string[]>
     */
    public function getHeaders(): array
    {
        return $this->headers->toArray();
    }

    public function headers(): HeaderCollection
    {
        return $this->headers;
    }

    public function write(string $content): self
    {
        if (!$this->body instanceof ContentStream) {
            $this->body = new ContentStream();
        }

        $this->body()->write($content);
        return $this;
    }

    public function content(string $content): self
    {
        $this->body = new ContentStream($content);
        return $this;
    }

    public function getContent(): string
    {
        return $this->body->getContents();
    }

    public function body(): ContentStream
    {
        if (!$this->body instanceof ContentStream) {
            $this->body = new ContentStream();
        }

        return $this->body;
    }

    /**
     * @param callable(callable(string): void): void $stream
     */
    public function stream(callable $stream): self
    {
        $this->body = new StreamedContent($stream);
        return $this;
    }

    public function getBody(): ResponseBodyInterface
    {
        return $this->body;
    }

    public function sendContent(): void
    {
        $this->body->emit();
    }
}
