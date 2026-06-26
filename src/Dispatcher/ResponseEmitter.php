<?php

declare(strict_types=1);

namespace Atom\Dispatcher;

use Atom\Http\Response;

class ResponseEmitter implements ResponseEmitterInterface
{
    public function emit(Response $response): void
    {
        $http_line = sprintf(
            'HTTP/%s %s %s',
            "1.1",
            $response->getStatus(),
            $response->getReasonPhrase()
        );
        header($http_line, true, $response->getStatus());
        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header("$name: $value", false);
            }
        }
        $response->sendContent();
    }
}
