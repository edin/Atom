<?php

namespace Atom\Interfaces;

use Atom\Http\Response;

interface IResponsable
{
    public function toResponse($context): Response;
}
