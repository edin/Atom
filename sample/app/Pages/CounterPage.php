<?php

declare(strict_types=1);

namespace App\Pages;

use Atom\Page\PageAction;
use Atom\Page\PageRoute;
use Atom\Page\State;

#[PageRoute("/counter", name: "counter")]
final class CounterPage extends AppPage
{
    public string $title = "Counter";

    #[State]
    public int $count = 0;

    #[State]
    public int $step = 1;

    #[PageAction("increment")]
    public function increment(): void
    {
        $this->count += $this->step;
    }

    #[PageAction("decrement")]
    public function decrement(): void
    {
        $this->count -= $this->step;
    }

    #[PageAction("reset")]
    public function reset(): void
    {
        $this->count = 0;
    }

    #[PageAction("setStep")]
    public function setStep(int $step): void
    {
        $this->step = max(1, min(10, $step));
    }

    public function stepClass(int $step): string
    {
        return $this->step === $step ? "selected" : "";
    }
}
