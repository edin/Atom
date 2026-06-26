<?php

declare(strict_types=1);

namespace Atom\Http;

interface ResponseBodyInterface
{
    public function emit(): void;

    public function getContents(): string;
}
