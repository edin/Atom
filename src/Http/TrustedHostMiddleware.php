<?php

declare(strict_types=1);

namespace Atom\Http;

use InvalidArgumentException;

final readonly class TrustedHostMiddleware implements MiddlewareInterface
{
    /** @var list<array{host: string, port: int|null, wildcard: bool}> */
    private array $trustedHosts;

    public function __construct(TrustedHostOptions $options)
    {
        $trustedHosts = [];
        foreach (array_filter(array_map("trim", explode(",", $options->hosts))) as $pattern) {
            $wildcard = str_starts_with($pattern, "*.");
            $value = $wildcard ? substr($pattern, 2) : $pattern;
            $host = $this->parse($value);
            if ($host === null) {
                throw new InvalidArgumentException("Trusted host pattern '{$pattern}' is invalid.");
            }
            if ($wildcard && filter_var($host["host"], FILTER_VALIDATE_IP) !== false) {
                throw new InvalidArgumentException("Trusted host IP addresses cannot use wildcards.");
            }
            $host["wildcard"] = $wildcard;
            $trustedHosts[] = $host;
        }
        $this->trustedHosts = $trustedHosts;
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        if ($this->trustedHosts === []) {
            return $handler->handle($request);
        }

        $candidate = $this->parse($request->getHost());
        if ($candidate === null || !$this->isTrusted($candidate)) {
            return (new Response())
                ->status(400)
                ->header("Content-Type", "text/plain; charset=utf-8")
                ->content("Invalid or untrusted Host header.");
        }

        return $handler->handle($request);
    }

    /** @param array{host: string, port: int|null} $candidate */
    private function isTrusted(array $candidate): bool
    {
        foreach ($this->trustedHosts as $trusted) {
            if ($trusted["port"] !== null && $trusted["port"] !== $candidate["port"]) {
                continue;
            }
            if (!$trusted["wildcard"] && hash_equals($trusted["host"], $candidate["host"])) {
                return true;
            }
            if ($trusted["wildcard"]
                && str_ends_with($candidate["host"], "." . $trusted["host"])
                && $candidate["host"] !== $trusted["host"]) {
                return true;
            }
        }
        return false;
    }

    /** @return array{host: string, port: int|null}|null */
    private function parse(string $value): ?array
    {
        $value = strtolower(trim($value));
        if ($value === "" || str_contains($value, "\r") || str_contains($value, "\n")) {
            return null;
        }

        if (str_starts_with($value, "[")) {
            if (preg_match('/^\[([^]]+)](?::([0-9]{1,5}))?$/D', $value, $matches) !== 1
                || filter_var($matches[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) {
                return null;
            }
            $host = $matches[1];
            $port = isset($matches[2]) ? $this->port($matches[2]) : null;
            return $port === false ? null : ["host" => $host, "port" => $port];
        }

        if (preg_match('/^([^:]+)(?::([0-9]{1,5}))?$/D', $value, $matches) !== 1) {
            return null;
        }
        $host = rtrim($matches[1], ".");
        if (!$this->validName($host)) {
            return null;
        }
        $port = isset($matches[2]) ? $this->port($matches[2]) : null;

        return $port === false ? null : ["host" => $host, "port" => $port];
    }

    private function validName(string $host): bool
    {
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            return true;
        }
        if (strlen($host) > 253) {
            return false;
        }
        foreach (explode(".", $host) as $label) {
            if ($label === "" || strlen($label) > 63
                || preg_match('/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/D', $label) !== 1) {
                return false;
            }
        }
        return true;
    }

    private function port(string $value): int|false
    {
        $port = (int) $value;
        return $port >= 1 && $port <= 65535 ? $port : false;
    }
}
