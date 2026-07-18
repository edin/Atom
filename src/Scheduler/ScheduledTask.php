<?php

declare(strict_types=1);

namespace Atom\Scheduler;

use Cron\CronExpression;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

abstract class ScheduledTask
{
    private string $expression = "* * * * *";
    private string $timezone = "UTC";
    private string $label = "";

    public function cron(string $expression): static
    {
        new CronExpression($expression);
        $this->expression = $expression;
        return $this;
    }

    public function everyMinute(): static
    {
        return $this->cron("* * * * *");
    }

    public function everyFiveMinutes(): static
    {
        return $this->cron("*/5 * * * *");
    }

    public function everyTenMinutes(): static
    {
        return $this->cron("*/10 * * * *");
    }

    public function everyFifteenMinutes(): static
    {
        return $this->cron("*/15 * * * *");
    }

    public function everyThirtyMinutes(): static
    {
        return $this->cron("*/30 * * * *");
    }

    public function hourly(): static
    {
        return $this->cron("0 * * * *");
    }

    public function daily(): static
    {
        return $this->cron("0 0 * * *");
    }

    public function weekly(): static
    {
        return $this->cron("0 0 * * 0");
    }

    public function monthly(): static
    {
        return $this->cron("0 0 1 * *");
    }

    public function weekdays(): static
    {
        return $this->field(4, "1-5");
    }

    public function weekends(): static
    {
        return $this->field(4, "0,6");
    }

    public function at(string $time): static
    {
        if (preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time) !== 1) {
            throw new InvalidArgumentException("Scheduled time '{$time}' must use 24-hour HH:MM format.");
        }

        [$hour, $minute] = explode(":", $time);
        $this->field(0, (string) (int) $minute);
        return $this->field(1, (string) (int) $hour);
    }

    public function timezone(string $timezone): static
    {
        new DateTimeZone($timezone);
        $this->timezone = $timezone;
        return $this;
    }

    public function name(string $label): static
    {
        $this->label = trim($label);
        return $this;
    }

    public function expression(): string
    {
        return $this->expression;
    }

    public function timezoneName(): string
    {
        return $this->timezone;
    }

    public function label(): string
    {
        return $this->label !== "" ? $this->label : $this->summary();
    }

    public function isDue(DateTimeImmutable $now): bool
    {
        return (new CronExpression($this->expression))->isDue($now, $this->timezone);
    }

    public function nextRun(DateTimeImmutable $now): DateTimeImmutable
    {
        $next = (new CronExpression($this->expression))->getNextRunDate($now, timeZone: $this->timezone);
        return DateTimeImmutable::createFromMutable($next);
    }

    abstract public function summary(): string;

    private function field(int $index, string $value): static
    {
        $fields = preg_split('/\s+/', trim($this->expression));
        if (!is_array($fields) || count($fields) !== 5) {
            throw new InvalidArgumentException("Frequency modifiers require a five-field cron expression.");
        }
        $fields[$index] = $value;
        return $this->cron(implode(" ", $fields));
    }
}
