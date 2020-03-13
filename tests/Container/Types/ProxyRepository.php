<?php

namespace Atom\Tests\Container\Types;

class ProxyRepository implements IRepository
{
    public $repository;

    public function __construct(IRepository $repository)
    {
        $this->repository = $repository;
    }
}