<?php

declare(strict_types=1);

namespace Atom\Publish;

use InvalidArgumentException;

final readonly class PublishFile
{
    public function __construct(
        public string $source,
        public string $destination
    ) {
        if (trim($this->source) === "") {
            throw new InvalidArgumentException("Publish file source cannot be empty.");
        }

        if (trim($this->destination) === "") {
            throw new InvalidArgumentException("Publish file destination cannot be empty.");
        }
    }
}
