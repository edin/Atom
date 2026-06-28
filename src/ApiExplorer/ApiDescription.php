<?php

declare(strict_types=1);

namespace Atom\ApiExplorer;

use JsonSerializable;

final readonly class ApiDescription implements JsonSerializable
{
    /**
     * @param ApiEndpointDescriptor[] $endpoints
     */
    public function __construct(public array $endpoints)
    {
    }

    /**
     * @return array{endpoints: ApiEndpointDescriptor[]}
     */
    public function jsonSerialize(): array
    {
        return [
            "endpoints" => $this->endpoints,
        ];
    }
}
