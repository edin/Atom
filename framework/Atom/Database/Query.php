<?php

namespace Atom\Database;

final class Table {
    public $name;
    public $alias;
}

final class Column {
    public $name;
    public $alias;
}

final class Join {
    public $table;
    public $column;
}



abstract class Query{
    private $table = null;

    public function from($table): self {
        $this->table = $table;
        return $this;
    }

    public function show() {
        var_dump($this);
    }

    public static function select(): SelectQuery {
        return new SelectQuery();
    }

    public static function delete(): DeleteQuery {
        return new DeleteQuery();
    }

    public static function update(): UpdateQuery {
        return new UpdateQuery();
    }

    public static function insert(): InsertQuery {
        return new InsertQuery();
    }
}

class Command {
    public function execute() {

    }
}

class SqlCommand extends Command {

}


Query::select()->from("users")->show();
Query::delete()->from("users")->show();

