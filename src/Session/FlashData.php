<?php

declare(strict_types=1);

namespace Atom\Session;

final readonly class FlashData
{
    public const KEY = "_atom_flash";

    /**
     * @param array<string, mixed> $session
     */
    public static function age(array &$session): void
    {
        $state = self::state($session[self::KEY] ?? null);
        $session[self::KEY] = [
            "current" => $state["next"],
            "next" => [],
        ];
    }

    /**
     * @return array{current: array<string, mixed>, next: array<string, mixed>}
     */
    public static function state(mixed $value): array
    {
        if (!is_array($value)) {
            return ["current" => [], "next" => []];
        }

        return [
            "current" => is_array($value["current"] ?? null) ? $value["current"] : [],
            "next" => is_array($value["next"] ?? null) ? $value["next"] : [],
        ];
    }
}
