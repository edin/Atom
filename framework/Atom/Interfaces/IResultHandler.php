<?php

namespace Atom\Interfaces;

use Psr\Http\Message\ResponseInterface;

interface IResultHandler
{
    public function isMatch($result): bool;
    public function process($result): ResponseInterface;
}
