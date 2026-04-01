<?php

declare(strict_types=1);

namespace App\Auth;

use App\Core\Session;

/**
 * Route protection guard.
 *
 * Call AuthGuard::require() at the top of any controller action
 * that must only be accessible to authenticated users.
 *
 * On failure the user is redirected to /login (no exception thrown).
 */
class AuthGuard
{
    /**
     * Abort with a redirect to /login if the user is not authenticated.
     */
    public static function require(): void
    {
        Session::start();

        if (!Session::has('user_id')) {
            header('Location: /login');
            exit;
        }
    }

    /**
     * Return true if a user is currently authenticated.
     */
    public static function isLoggedIn(): bool
    {
        Session::start();
        return Session::has('user_id');
    }

    /**
     * Redirect already-authenticated users away from guest-only pages (e.g. login).
     */
    public static function redirectIfLoggedIn(string $to = '/dashboard'): void
    {
        if (static::isLoggedIn()) {
            header("Location: $to");
            exit;
        }
    }
}
