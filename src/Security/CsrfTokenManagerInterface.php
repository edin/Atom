<?php

declare(strict_types=1);

namespace Atom\Security;

interface CsrfTokenManagerInterface
{
    public const FIELD_NAME = "_token";
    public const HEADER_NAME = "X-CSRF-Token";

    public function token(): string;

    public function refresh(): string;

    public function validate(?string $token): bool;

    public function clear(): void;
}
