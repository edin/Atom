<?php

declare(strict_types=1);

namespace Atom\Modules\ErrorPages;

use RuntimeException;
use Throwable;

class HttpException extends RuntimeException implements HttpExceptionInterface
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        private readonly int $httpStatus,
        string $message = "",
        private readonly array $httpHeaders = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, previous: $previous);
    }

    public function status(): int
    {
        return $this->httpStatus;
    }

    public function headers(): array
    {
        return $this->httpHeaders;
    }
}
