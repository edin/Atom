<?php

namespace Atom\Interfaces;

use Psr\Http\Message\ResponseInterface;

interface IResponsable
{
    public function toResponse($context): ResponseInterface;
}
