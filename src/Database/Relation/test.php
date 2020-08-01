<?php

use Atom\Database\Relation\RelationBuilder;

class Group
{
}

class User
{
};

class Role
{
};

/**
 *  CREATE TABLE groups (ID INT);
 *  CREATE TABLE users (ID INT);
 *  CREATE TABLE roles (ID INT);
 *  CREATE TABLE user_roles (ID INT, UserID int, RoleID int);
 */

$relation = new RelationBuilder(User::class);

$hasMany = $relation->hasMany(Role::class, "UserID");
// Select ... From roles Where UserID = :userId

$hasMany = $relation->hasManyTrough(Role::class, "user_roles", "UserID", "RoleID");
// Select ... From roles r
// Join user_roles ur 
// Where ur.RoleID = r.ID AND ur.UserID = :userId

$belongsTo = $relation->belongsTo(Group::class, "GroupID");         
// Select ... From groups where Id = :groupId
