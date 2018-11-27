<?php

namespace Atom\Interfaces;

use Psr\Http\Message\ResponseInterface;

interface IResultHandler
{
    public function isMatch($result): boolean;
    public function process($result): ResponseInterface;
}
