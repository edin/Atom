<?php

namespace Atom\Container\TypeFactory;

use Atom\Container\TypeInfo;

interface ITypeMatcher {
    public function matches(TypeInfo $typeInfo);
}
