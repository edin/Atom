<?php

declare(strict_types=1);

namespace Atom\Session;

use Atom\Http\Cookie;
use Atom\Http\CookieJar;
use Atom\Http\Request;
use RuntimeException;

final class NativeSession implements SessionInterface
{
    private bool $started = false;

    public function __construct(
        private readonly SessionOptions $options,
        private readonly Request $request,
        private readonly CookieJar $cookies
    ) {
    }

    public function start(): void
    {
        if ($this->started) {
            return;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            throw new RuntimeException("A PHP session is already active outside the current Atom request.");
        }

        $requestedId = $this->requestSessionId();
        session_name($this->options->name);
        session_id($requestedId ?? "");
        ini_set("session.use_strict_mode", $this->options->strictMode ? "1" : "0");
        ini_set("session.use_cookies", "0");
        session_cache_limiter("");

        if (!session_start()) {
            throw new RuntimeException("PHP session could not be started.");
        }

        FlashData::age($_SESSION);
        $this->started = true;

        if ($requestedId !== session_id()) {
            $this->queueSessionCookie();
        }
    }

    public function isStarted(): bool
    {
        return $this->started;
    }

    public function id(): string
    {
        $this->start();
        return session_id();
    }

    public function all(): array
    {
        $this->start();
        return array_diff_key($_SESSION, [FlashData::KEY => true]);
    }

    public function has(string $key): bool
    {
        $this->start();
        return array_key_exists($key, $_SESSION);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->start();
        return array_key_exists($key, $_SESSION) ? $_SESSION[$key] : $default;
    }

    public function put(string $key, mixed $value): void
    {
        $this->start();
        $_SESSION[$key] = $value;
    }

    public function remove(string $key): void
    {
        $this->start();
        unset($_SESSION[$key]);
    }

    public function pull(string $key, mixed $default = null): mixed
    {
        $value = $this->get($key, $default);
        $this->remove($key);
        return $value;
    }

    public function clear(): void
    {
        $this->start();
        $_SESSION = [];
    }

    public function regenerate(bool $deleteOldSession = false): void
    {
        $this->start();
        if (!session_regenerate_id($deleteOldSession)) {
            throw new RuntimeException("PHP session ID could not be regenerated.");
        }

        $this->queueSessionCookie();
    }

    public function invalidate(): void
    {
        $this->clear();
        $this->regenerate(true);
    }

    public function save(): void
    {
        if (!$this->started) {
            return;
        }

        session_write_close();
        $this->started = false;
    }

    private function sameSite(): string
    {
        $value = ucfirst(strtolower(trim($this->options->sameSite)));
        if (!in_array($value, ["Lax", "Strict", "None"], true)) {
            throw new RuntimeException("Session same-site policy must be Lax, Strict, or None.");
        }

        return $value;
    }

    private function requestSessionId(): ?string
    {
        $value = $this->request->cookies()->get($this->options->name);
        return $value !== null && preg_match('/^[A-Za-z0-9,-]+$/', $value) === 1 ? $value : null;
    }

    private function queueSessionCookie(): void
    {
        $cookie = Cookie::create($this->options->name, session_id())
            ->withPath($this->options->path)
            ->withDomain($this->options->domain)
            ->withSecure($this->options->secure ?? $this->request->isSecure())
            ->withHttpOnly($this->options->httpOnly)
            ->withSameSite($this->sameSite());

        if ($this->options->lifetime > 0) {
            $cookie = $cookie->expiresAfter($this->options->lifetime);
        }

        $this->cookies->set($cookie);
    }
}
