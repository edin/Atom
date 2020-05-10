<?php

namespace Atom\Database\Interfaces;

use PDO;

interface IDatabaseConnector
{
    public function open(): PDO;
    public function isActive(): bool;
    public function close(): void;
    public function getQueryCompiler(): IQueryCompiler;
}
