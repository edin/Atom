<?php

use Atom\Database\Query\JoinCriteria;
use Atom\Database\Query\Query;

include "../../../vendor/autoload.php";

// $query  = Query::delete()->from("users", "t")
//         ->where("id", Operator::equal(1))
//         ->where("skill_id", Operator::in([1,2,3,4,5]))
//         ->leftJoin("comments c", function (Join $join) {
//             $join->on("c.id", "t.user_id");
//         })
//         ->crossJoin("comments c", function (Join $join) {
//             $join->on("c.id", "t.user_id");
//         })
//         ->leftJoin(function ($join) {
//             $join->on("t.id", "c.user_id");
//         })
//         ->leftJoin(function ($join) {
//             $join->on("t.id", "c.user_id");
//         })
//         ;
// /*
//     ->whereGroup(function ($query) {
//         $query->orWhere("t.id", Operator::in([1,2,3,4,5]));
//         $query->orWhere("t.id", Operator::in([1,2,3,4,5]));
//         $query->orWhere("t.id", Operator::in([1,2,3,4,5]));
//         $query->orWhere("t.id", Operator::in([1,2,3,4,5]));
//         $query->orWhere("t.id", Operator::in([1,2,3,4,5]));
//     })
//     ->whereGroup(function ($query) {
//         $query->orWhere("t.id", Operator::in([1,2,3,4,5]));
//         $query->orWhere("t.id", Operator::in([1,2,3,4,5]));
//         $query->orWhere("t.id", Operator::in([1,2,3,4,5]));
//         $query->orWhere("t.id", Operator::in([1,2,3,4,5]));
//         $query->orWhere("t.id", Operator::in([1,2,3,4,5]));
//     });
// */

// $query = Query::delete()->from("users", "t")
//     ->where("t.id", Operator::in([1,2,3,4,5]))
//     ->where("t.id", Operator::in([1,2,3,4,5]))
//     ->where("t.id", Operator::in([1,2,3,4,5]))
//     ->where("t.id", Operator::in([1,2,3,4,5]))
//     ->where("t.id", Operator::in([1,2,3,4,5]));

// $command = $database->createCommand($query);
// $command->execute();


// $mapper->mapEntity(User::class, function (EntityMapper $entity) {
//     $entity->FirstName->string();
//     $entity->LastName->string();
//     $entity->mapProperty("Email")->string();
//     $entity->mapProperty("Comment")->toType(Comment::class);
//     $entity->mapProperty("Comments")->toArrayOf(Comment::class);
//     $entity->mapProperty("CommentsMap")->toIntegerMap(Comment::class);
//     $entity->mapProperty("CommentsMap")->toStringMap(Comment::class);
// });

// class User
// {
//     public $first_name;
//     public $last_name;
//     public $email;

//     public function assign(array $data)
//     {
//         $this->first_name = $data['first_name'] ?? "";
//         $this->last_name  = $data['last_name'] ?? "";
//         $this->email      = $data['email'] ?? "";
//     }
// }

// $user = new User();
// $user->assign([
//     'first_name' => "edin",
// ]);

// $query = Query::select()
//     ->from("users u")
//     ->columns([
//         "u.id id",
//         "u.first_name firstName",
//         "u.last_name lastName",
//         "u.email email",
//         "userCount" => Query::select()->from("users")->count()
//     ])
//     ->join("comments c", function (JoinCriteria $join) {
//         $join->on("users.c", "x.c");
//         $join->on("users.c", "x.c");
//         $join->on("users.c", "x.c");
//         $join->on("users.c", "x.c");
//     })
//     ->join("comments c", function (JoinCriteria $join) {
//         $join->on("users.c", "x.c");
//         $join->on("users.c", "x.c");
//         $join->on("users.c", "x.c");
//         $join->on("users.c", "x.c");
//     });
//     ;

//     $query->show();

$criteria = new JoinCriteria();
$criteria
    ->on("user.type_id", "status.id")
    ->on("comment.type_id", "status.id")
;

print_r($criteria);
