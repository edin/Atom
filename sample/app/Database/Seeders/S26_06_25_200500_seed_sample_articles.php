<?php

use Atom\Database\DatabaseConnection;
use Atom\Database\Seeder\Seeder;

return new class extends Seeder
{
    public function run(DatabaseConnection $connection): void
    {
        if ((int) $connection->scalar("SELECT COUNT(*) FROM categories") > 0) {
            return;
        }

        $now = date("Y-m-d H:i:s");

        $connection->execute(
            "INSERT INTO categories (name, slug) VALUES (:name, :slug)",
            [":name" => "Framework", ":slug" => "framework"]
        );
        $connection->execute(
            "INSERT INTO categories (name, slug) VALUES (:name, :slug)",
            [":name" => "Database", ":slug" => "database"]
        );

        $connection->execute(
            "INSERT INTO articles (category_id, title, summary, body, is_published, created_at, updated_at)
             VALUES (:category_id, :title, :summary, :body, 1, :created_at, :updated_at)",
            [
                ":category_id" => 1,
                ":title" => "A smaller Atom sample",
                ":summary" => "This app uses the new bootstrap, router facade, views, response conversion, and DI container.",
                ":body" => "The goal of this sample is to stay boring in the best way: a few routes, a simple repository, and enough real framework usage to expose rough edges quickly.",
                ":created_at" => $now,
                ":updated_at" => $now,
            ]
        );

        $connection->execute(
            "INSERT INTO articles (category_id, title, summary, body, is_published, created_at, updated_at)
             VALUES (:category_id, :title, :summary, :body, 1, :created_at, :updated_at)",
            [
                ":category_id" => 2,
                ":title" => "SQLite through the new Db layer",
                ":summary" => "The repository loads entities and eager-loads categories with the new relation support.",
                ":body" => "It is intentionally tiny, but it exercises the path we care about: DbSelect, ORM attributes, relation loading, and JSON result handling.",
                ":created_at" => $now,
                ":updated_at" => $now,
            ]
        );
    }
};
