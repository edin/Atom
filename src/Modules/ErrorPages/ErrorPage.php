<?php

declare(strict_types=1);

namespace Atom\Modules\ErrorPages;

use Atom\Http\Request;
use Throwable;

final readonly class ErrorPage
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public int $status,
        public string $title,
        public string $message,
        public ?string $id,
        public Request $request,
        public array $headers = [],
        public ?Throwable $exception = null
    ) {
    }
}
