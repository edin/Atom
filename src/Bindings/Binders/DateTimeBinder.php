<?php

namespace Atom\Bindings\Binders;

use Atom\Bindings\BindingContext;
use DateTime;
use DateTimeImmutable;
use Atom\Bindings\BindingResult;
use Atom\Bindings\BindingTargetInterface;
use Atom\Bindings\ModelBinderInterface;

final class DateTimeBinder implements ModelBinderInterface
{
    public array $dateFormats = [
        'Y-m-d\TH:i:s.u\Z'=> ['resetTime' => false],
        'Y-m-d H:i:s'=> ['resetTime' => false],
        'Y-m-d' => ['resetTime' => true]
    ];

    public function bindModel(BindingTargetInterface $target, BindingContext $context): ?BindingResult
    {
        $typeName = $target->getTypeName();

        if ($typeName !== "DateTime" && $typeName !== "DateTimeImmutable") {
            return null;
        }

        $value = $target->getValue();
        $result = null;

        foreach ($this->dateFormats as $format => $options) {
            $result = DateTime::createFromFormat($format, $value);

            if ($result) {
                if (isset($options['resetTime']) && $options['resetTime']) {
                    $result->setTime(0, 0, 0);
                }
                break;
            }
        }

        if ($result) {
            if ($typeName === "DateTimeImmutable") {
                $result = DateTimeImmutable::createFromMutable($result);
            }
            return new BindingResult($result);
        }

        if ($target->allowsNull()) {
            return new BindingResult(null);
        }

        return null;
    }
}
