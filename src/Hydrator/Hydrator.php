<?php

namespace Atom\Hydrator;

class Hydrator
{
    public function getHydrator(string $typeName): IHydrator
    {
        //TODO: If type has constructor with parameters then return constructor based hydrator
        return new PropertyHydrator($typeName);
    }
}
