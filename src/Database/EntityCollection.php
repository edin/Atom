<?php

declare(strict_types=1);

namespace Atom\Database;

use Atom\Collections\Collection;

class EntityCollection extends Collection
{
    /**
     * @return self
     */
    public static function from(iterable $items)
    {
        return new self($items);
    }
}
