<?php

namespace Atom\Hydrator;

interface IHydrator
{
    public function hydrate(array $data);
    public function extract(object $instance): array;
}
