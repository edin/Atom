<?php

namespace Atom\Interfaces;

interface IUser
{
    public function getId();
    public function getUsername(): string;
    public function getEmail(): string;
    public function getDisplayName(): string;
    public function getAuthKey(): string;
    public function getAvatarUrl(): string;
    public function hasRole(string $role): bool;
    public function isAdmin(): bool;
    public function isGuest(): bool;
}
