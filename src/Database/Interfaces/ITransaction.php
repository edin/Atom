<?php

namespace Atom\Database\Interfaces;

interface ITransaction
{
    public function commit(): void;
    public function rollback(): void;
}
