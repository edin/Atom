<?php

namespace App\Models;

class UserRepository
{
    public function findAll()
    {
        return [
            User::from(1, "user1", "user1@mail.com"),
            User::from(2, "user2", "user2@mail.com"),
            User::from(3, "user3", "user3@mail.com"),
            User::from(4, "user4", "user4@mail.com"),
            User::from(5, "user5", "user5@mail.com"),
            User::from(6, "user6", "user6@mail.com"),
            User::from(7, "user7", "user7@mail.com"),
            User::from(8, "user8", "user8@mail.com"),
            User::from(9, "user9", "user9@mail.com"),
            User::from(10, "user10", "user10@mail.com"),
        ];
    }
}
