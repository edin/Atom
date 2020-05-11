<?php

declare(strict_types=1);

namespace Atom\Validation\Rules;

use DateTime;
use DateTimeInterface;

final class Date extends AbstractRule
{
    protected $errorMessage = "Value is not in valid date format";
    private $formats = [
        DateTime::ISO8601,
        'Y-m-d\TH:i:s.u\Z',
        'Y-m-d\TH:i:s+',
        'Y-m-d H:i:s',
        'Y-m-d'
    ];

    public function isValid($value): bool
    {
        if ($value instanceof DateTimeInterface) {
            return true;
        }
        foreach ($this->formats as $format) {
            $date = DateTime::createFromFormat($format, $value);
            $lastErrors = DateTime::getLastErrors();
            $hasErrors = $lastErrors['warning_count'] + $lastErrors['error_count'];
            if ($date !== false && $hasErrors === 0) {
                return true;
            }
        }
        return false;
    }
}
