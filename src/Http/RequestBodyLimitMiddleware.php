<?php

declare(strict_types=1);

namespace Atom\Http;

use InvalidArgumentException;

final readonly class RequestBodyLimitMiddleware implements MiddlewareInterface
{
    public function __construct(private RequestBodyLimitOptions $options)
    {
        if ($options->maxBytes < 0) {
            throw new InvalidArgumentException("Request body limit cannot be negative.");
        }
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        if ($this->options->maxBytes === 0) {
            return $handler->handle($request);
        }

        $contentLength = $request->headers()->get(
            "Content-Length",
            $request->server()->string("CONTENT_LENGTH")
        ) ?? "";

        if ($contentLength !== "") {
            $contentLength = trim($contentLength);
            if ($contentLength === "" || preg_match('/^[0-9]+$/D', $contentLength) !== 1) {
                return $this->invalidLength();
            }
            if ($this->greaterThanLimit($contentLength)) {
                return $this->tooLarge();
            }
        }

        if (strlen($request->getBody()) > $this->options->maxBytes) {
            return $this->tooLarge();
        }

        return $handler->handle($request);
    }

    private function greaterThanLimit(string $length): bool
    {
        $normalized = ltrim($length, "0");
        $normalized = $normalized === "" ? "0" : $normalized;
        $limit = (string) $this->options->maxBytes;

        return strlen($normalized) > strlen($limit)
            || (strlen($normalized) === strlen($limit) && strcmp($normalized, $limit) > 0);
    }

    private function invalidLength(): Response
    {
        return (new Response())
            ->status(400)
            ->header("Content-Type", "text/plain; charset=utf-8")
            ->content("Invalid Content-Length header.");
    }

    private function tooLarge(): Response
    {
        return (new Response())
            ->status(413)
            ->header("Content-Type", "text/plain; charset=utf-8")
            ->content("Request body is too large.");
    }
}
