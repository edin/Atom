<?php

declare(strict_types=1);

namespace Atom\Http;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;

final readonly class Cookie
{
    public function __construct(
        public string $name,
        public string $value = "",
        public ?DateTimeImmutable $expires = null,
        public ?int $maxAge = null,
        public string $path = "/",
        public string $domain = "",
        public bool $secure = false,
        public bool $httpOnly = true,
        public ?string $sameSite = "Lax"
    ) {
        if ($this->name === "" || preg_match('/^[!#$%&\'*+\-.^_`|~0-9A-Za-z]+$/D', $this->name) !== 1) {
            throw new InvalidArgumentException("Cookie name '{$this->name}' is invalid.");
        }

        if (preg_match('/[;\x00-\x1F\x7F]/', $this->path) === 1) {
            throw new InvalidArgumentException("Cookie path contains invalid characters.");
        }

        if (preg_match('/[;\x00-\x20\x7F]/', $this->domain) === 1) {
            throw new InvalidArgumentException("Cookie domain contains invalid characters.");
        }

        if ($this->sameSite !== null && !in_array($this->sameSite, ["Lax", "Strict", "None"], true)) {
            throw new InvalidArgumentException("Cookie same-site policy must be Lax, Strict, None, or null.");
        }

    }

    public static function create(string $name, string $value = ""): self
    {
        return new self($name, $value);
    }

    public static function forget(string $name, string $path = "/", string $domain = ""): self
    {
        return new self(
            $name,
            expires: new DateTimeImmutable("@0"),
            maxAge: 0,
            path: $path,
            domain: $domain
        );
    }

    public function expiresAt(DateTimeInterface $expires): self
    {
        return $this->copy(expires: DateTimeImmutable::createFromInterface($expires));
    }

    public function expiresAfter(int $seconds): self
    {
        $seconds = max(0, $seconds);
        return $this->copy(
            expires: (new DateTimeImmutable())->modify("+{$seconds} seconds"),
            maxAge: $seconds
        );
    }

    public function withMaxAge(int $seconds): self
    {
        return $this->copy(maxAge: max(0, $seconds));
    }

    public function withPath(string $path): self
    {
        return $this->copy(path: $path);
    }

    public function withDomain(string $domain): self
    {
        return $this->copy(domain: $domain);
    }

    public function withSecure(bool $secure = true): self
    {
        return $this->copy(secure: $secure);
    }

    public function withHttpOnly(bool $httpOnly = true): self
    {
        return $this->copy(httpOnly: $httpOnly);
    }

    public function withSameSite(?string $sameSite): self
    {
        $sameSite = $sameSite === null ? null : ucfirst(strtolower(trim($sameSite)));
        return $this->copy(sameSite: $sameSite);
    }

    public function toHeader(): string
    {
        if ($this->sameSite === "None" && !$this->secure) {
            throw new InvalidArgumentException("SameSite=None cookies must be secure.");
        }

        $parts = [$this->name . "=" . rawurlencode($this->value)];

        if ($this->expires !== null) {
            $parts[] = "Expires=" . $this->expires
                ->setTimezone(new DateTimeZone("GMT"))
                ->format("D, d M Y H:i:s") . " GMT";
        }

        if ($this->maxAge !== null) {
            $parts[] = "Max-Age=" . $this->maxAge;
        }

        if ($this->path !== "") {
            $parts[] = "Path=" . $this->path;
        }

        if ($this->domain !== "") {
            $parts[] = "Domain=" . $this->domain;
        }

        if ($this->secure) {
            $parts[] = "Secure";
        }

        if ($this->httpOnly) {
            $parts[] = "HttpOnly";
        }

        if ($this->sameSite !== null) {
            $parts[] = "SameSite=" . $this->sameSite;
        }

        return implode("; ", $parts);
    }

    private function copy(
        ?DateTimeImmutable $expires = null,
        ?int $maxAge = null,
        ?string $path = null,
        ?string $domain = null,
        ?bool $secure = null,
        ?bool $httpOnly = null,
        string|null|false $sameSite = false
    ): self {
        return new self(
            $this->name,
            $this->value,
            $expires ?? $this->expires,
            $maxAge ?? $this->maxAge,
            $path ?? $this->path,
            $domain ?? $this->domain,
            $secure ?? $this->secure,
            $httpOnly ?? $this->httpOnly,
            $sameSite === false ? $this->sameSite : $sameSite
        );
    }
}
