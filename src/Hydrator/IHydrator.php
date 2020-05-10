<?php

namespace Atom\Hydrator;

interface IHydrator
{
    function hydrate(array $data);
    function extract(): array;
}
