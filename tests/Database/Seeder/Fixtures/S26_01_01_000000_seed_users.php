<?php

use Atom\Database\DatabaseConnection;
use Atom\Database\Seeder\Seeder;

return new class extends Seeder
{
    public function run(DatabaseConnection $connection): void
    {
        if ((int) $connection->scalar("SELECT COUNT(*) FROM seed_users") > 0) {
            return;
        }

        $connection->execute(
            "INSERT INTO seed_users (name) VALUES (:name)",
            [":name" => "Edin"]
        );
    }
};
