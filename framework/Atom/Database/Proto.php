<?php

$query  = Query::delete()->from("users", "t")
        ->where("id", Operator::equal(1))
        ->where("skill_id", Operator::in([1,2,3,4,5]))
        ->leftJoin("comments c",  function (Join $join) { $join->on("c.id", "t.user_id"); })
        ->crossJoin("comments c", function (Join $join) { $join->on("c.id", "t.user_id"); })
        ->rightJoin("comments c", function (Join $join) { $join->on("c.id", "t.user_id"); })
        ->leftJoin(function ($join) {
            $join->on("t.id", "c.user_id");
        })
        ->leftJoin(function ($join) {
            $join->on("t.id", "c.user_id");
        })
        ;

        /***
        ->whereGroup(function ($query) {
            $query->orWhere("t.id", Operator::in([1,2,3,4,5]));
            $query->orWhere("t.id", Operator::in([1,2,3,4,5]));
            $query->orWhere("t.id", Operator::in([1,2,3,4,5]));
            $query->orWhere("t.id", Operator::in([1,2,3,4,5]));
            $query->orWhere("t.id", Operator::in([1,2,3,4,5]));
        })
        ->whereGroup(function ($query) {
            $query->orWhere("t.id", Operator::in([1,2,3,4,5]));
            $query->orWhere("t.id", Operator::in([1,2,3,4,5]));
            $query->orWhere("t.id", Operator::in([1,2,3,4,5]));
            $query->orWhere("t.id", Operator::in([1,2,3,4,5]));
            $query->orWhere("t.id", Operator::in([1,2,3,4,5]));
        });
        ***/

$query   = Query::delete()->from("users", "t")
    ->where("t.id", Operator::in([1,2,3,4,5]))
    ->where("t.id", Operator::in([1,2,3,4,5]))
    ->where("t.id", Operator::in([1,2,3,4,5]))
    ->where("t.id", Operator::in([1,2,3,4,5]))
    ->where("t.id", Operator::in([1,2,3,4,5]));

$command = $database->createCommand($query);
$command->execute();


$mapper->mapEntity(User::class, function(EntityMapper $entity){
    $entity->mapProperty("FirstName")->string();
    $entity->mapProperty("LastName")->string();
    $entity->mapProperty("Email")->string();
    $entity->mapProperty("Comment")->toType(Comment::class);
    $entity->mapProperty("Comments")->toArrayOf(Comment::class);
    $entity->mapProperty("CommentsMap")->toIntegerMap(Comment::class);
    $entity->mapProperty("CommentsMap")->toStringMap(Comment::class);
});

class User {
    public $first_name;
    public $last_name;
    public $email;

    public function assign(array $data) {
        $this->first_name = $data['first_name'] ?? "";
        $this->last_name  = $data['last_name'] ?? "";
        $this->email      = $data['email'] ?? "";
    }

    public function decoder() {

    }
}

$user = new User();
$user->assign([
    'first_name' => "edin",
]);