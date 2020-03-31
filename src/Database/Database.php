<?php

namespace Atom\Database;

use Exception;
use Atom\Database\Interfaces\IConnection;

class Database
{
    // private static $instance;
    // public static function initialize(Database $instance)
    // {
    //     self::$instance = $instance;
    // }
    // public static function instance(): Database
    // {
    //     return self::$instance;
    // }

    public function __construct()
    {
        // Figure what to do here
    }

    public function getReadConnection(): IConnection
    {
        throw new Exception("Not implemented");
    }

    public function getWriteConnection(): IConnection
    {
        throw new Exception("Not implemented");
    }
}
