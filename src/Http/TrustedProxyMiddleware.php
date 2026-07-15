<?php

declare(strict_types=1);

namespace Atom\Http;

final readonly class TrustedProxyMiddleware implements MiddlewareInterface
{
    /** @var string[] */
    private array $trustedProxies;

    public function __construct(TrustedProxyOptions $options)
    {
        $this->trustedProxies = array_values(array_filter(
            array_map("trim", explode(",", $options->proxies)),
            static fn(string $proxy): bool => $proxy !== ""
        ));
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $peer = $request->getClientIp();
        if ($peer === "" || !$this->isTrusted($peer)) {
            return $handler->handle($request);
        }

        $server = $request->getServerParams();
        $headers = $request->headers()->toArray();
        $forwarded = $this->forwarded($request);

        $clientIp = $this->clientIp($peer, $forwarded["for"] ?? $request->headers()->get("X-Forwarded-For", ""));
        if ($clientIp !== null) {
            $server["REMOTE_ADDR"] = $clientIp;
        }

        $proto = $this->first($forwarded["proto"] ?? $request->headers()->get("X-Forwarded-Proto", ""));
        if ($proto === "http" || $proto === "https") {
            $server["HTTPS"] = $proto === "https" ? "on" : "off";
            $server["REQUEST_SCHEME"] = $proto;
            $server["SERVER_PORT"] = $proto === "https" ? 443 : 80;
        }

        $host = $this->validHost($this->first(
            $forwarded["host"] ?? $request->headers()->get("X-Forwarded-Host", "")
        ));
        if ($host !== null) {
            $server["HTTP_HOST"] = $host;
            $headers["Host"] = [$host];
            $port = $this->hostPort($host);
            if ($port !== null) {
                $server["SERVER_PORT"] = $port;
            }
        }

        return $handler->handle($request->withServerParams($server, $headers));
    }

    /** @return array{for?: string, proto?: string, host?: string} */
    private function forwarded(Request $request): array
    {
        $header = $request->headers()->get("Forwarded", "") ?? "";
        $element = trim(explode(",", $header, 2)[0]);
        if ($element === "") {
            return [];
        }

        $values = [];
        foreach (explode(";", $element) as $pair) {
            if (!str_contains($pair, "=")) {
                continue;
            }
            [$name, $value] = array_map("trim", explode("=", $pair, 2));
            $name = strtolower($name);
            if (in_array($name, ["for", "proto", "host"], true)) {
                $values[$name] = trim($value, "\" ");
            }
        }

        return $values;
    }

    private function clientIp(string $peer, ?string $forwardedFor): ?string
    {
        $chain = array_values(array_filter(array_map(
            fn(string $value): string => $this->normalizeIp($value),
            explode(",", $forwardedFor ?? "")
        )));
        $current = $peer;

        while ($chain !== [] && $this->isTrusted($current)) {
            $current = array_pop($chain);
        }

        return filter_var($current, FILTER_VALIDATE_IP) !== false ? $current : null;
    }

    private function normalizeIp(string $value): string
    {
        $value = trim($value, " \t\n\r\0\x0B\"");
        if (str_starts_with($value, "[")) {
            $end = strpos($value, "]");
            return $end === false ? "" : substr($value, 1, $end - 1);
        }

        if (substr_count($value, ":") === 1 && str_contains($value, ".")) {
            return explode(":", $value, 2)[0];
        }

        return $value;
    }

    private function isTrusted(string $ip): bool
    {
        foreach ($this->trustedProxies as $proxy) {
            if ($this->matches($ip, $proxy)) {
                return true;
            }
        }
        return false;
    }

    private function matches(string $ip, string $proxy): bool
    {
        if (!str_contains($proxy, "/")) {
            return filter_var($ip, FILTER_VALIDATE_IP) !== false
                && inet_pton($ip) === inet_pton($proxy);
        }

        [$network, $prefixText] = explode("/", $proxy, 2);
        $address = @inet_pton($ip);
        $networkAddress = @inet_pton($network);
        if ($address === false || $networkAddress === false || strlen($address) !== strlen($networkAddress)) {
            return false;
        }

        $prefix = filter_var($prefixText, FILTER_VALIDATE_INT);
        $maxBits = strlen($address) * 8;
        if ($prefix === false || $prefix < 0 || $prefix > $maxBits) {
            return false;
        }

        $bytes = intdiv($prefix, 8);
        $bits = $prefix % 8;
        if (substr($address, 0, $bytes) !== substr($networkAddress, 0, $bytes)) {
            return false;
        }
        if ($bits === 0) {
            return true;
        }

        $mask = (0xff << (8 - $bits)) & 0xff;
        return (ord($address[$bytes]) & $mask) === (ord($networkAddress[$bytes]) & $mask);
    }

    private function first(?string $value): string
    {
        return strtolower(trim(explode(",", $value ?? "", 2)[0]));
    }

    private function validHost(string $host): ?string
    {
        if ($host === "" || str_contains($host, "\r") || str_contains($host, "\n")) {
            return null;
        }

        return preg_match('/^(?:\[[0-9a-f:.]+\]|[a-z0-9.-]+)(?::[0-9]{1,5})?$/i', $host) === 1
            ? $host
            : null;
    }

    private function hostPort(string $host): ?int
    {
        if (preg_match('/:(\d+)$/', $host, $matches) !== 1) {
            return null;
        }
        $port = (int) $matches[1];
        return $port >= 1 && $port <= 65535 ? $port : null;
    }
}
