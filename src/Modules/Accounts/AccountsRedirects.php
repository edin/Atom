<?php

declare(strict_types=1);

namespace Atom\Modules\Accounts;

final readonly class AccountsRedirects
{
    public function local(?string $target, string $fallback = "/"): string
    {
        $fallback = $this->valid($fallback) ? $fallback : "/";
        $target = trim($target ?? "");

        return $this->valid($target) ? $target : $fallback;
    }

    private function valid(string $target): bool
    {
        if ($target === "" || $target[0] !== "/" || str_starts_with($target, "//")) {
            return false;
        }
        if (str_contains($target, "\\") || preg_match('/[\x00-\x1F\x7F]/', $target) === 1) {
            return false;
        }
        if (preg_match('/%(?:0a|0d|5c)/i', $target) === 1) {
            return false;
        }

        $parts = parse_url($target);
        return is_array($parts)
            && !isset($parts["scheme"], $parts["host"], $parts["user"], $parts["pass"]);
    }
}
