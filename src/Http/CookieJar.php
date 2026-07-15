<?php

declare(strict_types=1);

namespace Atom\Http;

final class CookieJar
{
    /** @var array<string, Cookie> */
    private array $cookies = [];

    public function set(Cookie $cookie): self
    {
        $cookie->toHeader();
        $this->cookies[$this->key($cookie)] = $cookie;
        return $this;
    }

    public function remove(string $name, string $path = "/", string $domain = ""): self
    {
        return $this->set(Cookie::forget($name, $path, $domain));
    }

    /**
     * @return Cookie[]
     */
    public function all(): array
    {
        return array_values($this->cookies);
    }

    public function isEmpty(): bool
    {
        return $this->cookies === [];
    }

    public function apply(Response $response): Response
    {
        foreach ($this->cookies as $cookie) {
            $response->addHeader("Set-Cookie", $cookie->toHeader());
        }

        $this->cookies = [];
        return $response;
    }

    private function key(Cookie $cookie): string
    {
        return $cookie->name . "\0" . $cookie->path . "\0" . strtolower($cookie->domain);
    }
}
