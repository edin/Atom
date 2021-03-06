<?php

// use Atom\Database\Query\Ast\BinaryExpression;
// use Atom\Database\Query\Ast\GroupExpression;
// use Atom\Database\Query\Criteria;
// use Atom\Database\Query\Operator;
// use Atom\Database\Query\Query;

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
//     ->join("comments c", function (Criteria $join) {
//         $join->on("users.c", "x.c");
//         $join->on("users.c", "x.c");
//         $join->on("users.c", "x.c");
//         $join->on("users.c", "x.c");
//     })
//     ->join("comments c", function (Criteria $join) {
//         $join->on("users.c", "x.c");
//         $join->on("users.c", "x.c");
//         $join->on("users.c", "x.c");
//         $join->on("users.c", "x.c");
//     });
//     ;
//     $query->show();

// $criteria = new Criteria();
// $criteria
//     ->on("user.type_id", "status.id")
//     // ->join("comments c", function (Criteria $join) {
//     //     $join->on("user.comment_id", "c.id");
//     // })
//     ->orGroup(function (Criteria $c) {
//         $c->on("a", "b");
//         $c->on("a", "b");
//         $c->on("a", "b");
//         $c->on("a", "b");
//     })
//     ;

// $query = Query::select()
//     ->from("users u")
//     ->columns(["u.first_name x"])
//     ->join("comments c", function (Criteria $join) {
//         $join->group(function (Criteria $group) {
//             $group->on("u.comment_id", Operator::less(1));
//             $group->on("u.comment_id", "c.id");
//             $group->orOn("u.comment_id", "c.id");
//             $group->on("u.comment_id", "c.id");
//         });

//         $join->group(function (Criteria $group) {
//             $group->on("a", Operator::equal(1));
//             $group->on("a", Operator::equal(1));
//             $group->on("a", Operator::equal(1));
//             $group->on("a", Operator::equal(1));
//         });

//         $join->orGroup(function (Criteria $group) {
//             $group->on("a", Operator::equal(1));
//             $group->on("a", Operator::equal(1));
//             $group->on("a", Operator::equal(1));
//             $group->on("a", Operator::equal(1));
//         });

//         $join->orGroup(function (Criteria $group) {
//             $group->on("a", Operator::equal(1));
//             $group->on("a", Operator::equal(1));
//             $group->on("a", Operator::equal(1));
//             $group->on("a", Operator::equal(1));
//         });

//         $join->orGroup(function (Criteria $group) {
//             $group->on("a", Operator::equal(1));
//             $group->on("a", Operator::equal(1));
//             $group->on("a", Operator::equal(1));
//             $group->on("a", Operator::equal(1));
//         });

//         $join->orGroup(function (Criteria $group) {
//             $group->on("a", Operator::equal(1));
//             $group->on("a", Operator::in([1, 2, 3, 4, 5]));
//             $group->on("a", Operator::equal(1));
//             $group->on("a", Operator::equal(1));
//         });
//     });

// $join = $query->getJoins();

// function buildCriteria($node)
// {
//     if ($node instanceof BinaryExpression) {
//         $newLine = "";
//         if ($node->rightNode instanceof GroupExpression) {
//             $newLine = "\n";
//         }

//         $result = buildCriteria($node->leftNode) . " {$node->operator} $newLine" . buildCriteria($node->rightNode);
//         if ($node->operator === "AND" || $node->operator === "OR") {
//             $result = "{$result}";
//         }
//         return $result;
//     } elseif ($node instanceof GroupExpression) {
//         $result = buildCriteria($node->node);
//         return "($result)";
//     } elseif ($node instanceof Operator) {
//         return $node->getValue();
//     } elseif (is_object($node)) {
//         return get_class($node);
//     } else {
//         return (string) $node;
//     }
// }

// //print_r($join[0]->getJoinCondition()->getExpression());
// echo "\n";
// echo buildCriteria($join[0]->getJoinCondition()->getExpression());
// echo "\n";
