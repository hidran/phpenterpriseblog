<?php

declare(strict_types=1);
// Global helpers — kept thin. Most logic lives in App\Support typed classes.

function isUserLoggedin(): bool
{
    return (bool) ($_SESSION['loggedin'] ?? false);
}

function getUserLoggedInFullname(): string
{
    return (string) ($_SESSION['userData']['username'] ?? '');
}

function getUserRole(): string
{
    return (string) ($_SESSION['userData']['roletype'] ?? '');
}

function getUserEmail(): string
{
    return (string) ($_SESSION['userData']['email'] ?? '');
}

function getUserId(): int
{
    return (int) ($_SESSION['userData']['id'] ?? 0);
}

function isUserAdmin(): bool
{
    return getUserRole() === 'admin';
}

function userCanUpdate(): bool
{
    $role = getUserRole();
    return $role === 'admin' || $role === 'editor';
}

function userCanDelete(): bool
{
    return isUserAdmin();
}
