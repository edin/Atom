<?php

declare(strict_types=1);

namespace Atom\Page;

interface PageStateSerializerInterface
{
    public function serialize(Page $page): string;

    public function deserialize(Page $page, ?string $state): void;
}
