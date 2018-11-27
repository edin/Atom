<?php

namespace Atom\Interfaces;

interface IUser {
    public function getId();
    public function getUsername(): string;
    public function getEmail(): string;
    public function getDisplayName(): string;
    public function getAuthKey(): string;
    public function hasRole(string $role): bool;
}