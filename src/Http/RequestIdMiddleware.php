<?php

declare(strict_types=1);

namespace Atom\Http;

use InvalidArgumentException;

final readonly class RequestIdMiddleware implements MiddlewareInterface
{
    public function __construct(private RequestIdOptions $options)
    {
        if (preg_match("/^[!#$%&'*+.^_`|~0-9A-Za-z-]+$/D", $options->headerName) !== 1) {
            throw new InvalidArgumentException("Request ID header name must be a valid HTTP token.");
        }
        if ($options->maxLength < 32) {
            throw new InvalidArgumentException("Request ID maximum length must be at least 32.");
        }
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $incoming = $request->headers()->get($this->options->headerName, "") ?? "";
        $requestId = $this->options->trustIncoming && $this->isValid($incoming)
            ? $incoming
            : bin2hex(random_bytes(16));

        $response = $handler->handle($request->withHeader($this->options->headerName, $requestId));
        if (!$response->headers()->has($this->options->headerName)) {
            $response->header($this->options->headerName, $requestId);
        }

        return $response;
    }

    private function isValid(string $value): bool
    {
        return $value !== ""
            && strlen($value) <= $this->options->maxLength
            && preg_match('/^[A-Za-z0-9._-]+$/D', $value) === 1;
    }
}
