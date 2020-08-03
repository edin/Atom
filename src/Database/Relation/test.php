<?php

use Atom\Database\Mapping\Mapping;

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

$mapping = new Mapping();
$mapping->setEntityClass(User::class);
$mapping->relation("roles")->hasMany(Role::class, "UserID");
$mapping->relation("rolesTrough")->hasManyTrough(Role::class, "UserID", "user_roles", "UserID", "RoleID");
$mapping->relation("group")->belongsTo(Group::class, "GroupID");
$relations = $mapping->getRelations();
