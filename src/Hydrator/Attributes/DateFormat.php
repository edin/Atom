<?php

declare(strict_types=1);

namespace Atom\Hydrator\Attributes;

use Atom\Hydrator\ValueTransformerInterface;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER)]
final readonly class DateFormat implements ValueTransformerInterface
{
    public function __construct(public string $format)
    {
    }

    public function transform(mixed $value): mixed
    {
        if ($value === null || $value instanceof \DateTimeInterface) {
            return $value;
        }

        if (!is_scalar($value)) {
            return $value;
        }

        return \DateTimeImmutable::createFromFormat($this->format, (string) $value) ?: $value;
    }
}
