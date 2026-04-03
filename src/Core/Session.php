<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Secure session manager.
 *
 * Configures PHP session with hardened settings before starting:
 *  - HttpOnly cookie  (prevents JS access)
 *  - SameSite=Strict  (blocks cross-site sends)
 *  - Secure flag      (HTTPS only, skipped in non-production)
 *  - Strict mode      (reject unrecognised session IDs)
 *
 * Call Session::start() once at bootstrap — before any output.
 *
 * Usage:
 *   Session::start();
 *   Session::set('user_id', 42);
 *   $id = Session::get('user_id');
 *   Session::regenerate(); // after successful login
 *   Session::destroy();    // on logout
 */
class Session
{
    private static bool $started = false;

    /**
     * Start the session with secure cookie parameters.
     * Safe to call multiple times — starts only once.
     */
    public static function start(): void
    {
        if (static::$started || session_status() === PHP_SESSION_ACTIVE) {
            static::$started = true;
            return;
        }

        $isProduction = defined('APP_ENV') && APP_ENV === 'production';

        session_set_cookie_params([
            'lifetime' => 0,          // browser-session cookie
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isProduction,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);

        // Strict mode: PHP will not accept an unknown session ID from the client
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');

        session_start();
        static::$started = true;
    }

    /**
     * Regenerate the session ID.
     * Must be called after a privilege change (login, role change).
     *
     * @param bool $deleteOld Delete the old session file immediately
     */
    public static function regenerate(bool $deleteOld = true): void
    {
        static::assertStarted();
        session_regenerate_id($deleteOld);
    }

    /**
     * Set a session value.
     */
    public static function set(string $key, mixed $value): void
    {
        static::assertStarted();
        $_SESSION[$key] = $value;
    }

    /**
     * Get a session value, returning $default if not present.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        static::assertStarted();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Remove a single session key.
     * Alias kept minimal — for flash message cleanup, etc.
     */
    public static function forget(string $key): void
    {
        static::assertStarted();
        unset($_SESSION[$key]);
    }

    /**
     * Check whether a session key exists.
     */
    public static function has(string $key): bool
    {
        static::assertStarted();
        return isset($_SESSION[$key]);
    }

    /**
     * Remove a session key.
     */
    public static function remove(string $key): void
    {
        static::assertStarted();
        unset($_SESSION[$key]);
    }

    /**
     * Destroy the session entirely (logout).
     */
    public static function destroy(): void
    {
        static::assertStarted();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                [
                    'expires'  => time() - 42000,
                    'path'     => $params['path'],
                    'domain'   => $params['domain'],
                    'secure'   => $params['secure'],
                    'httponly' => $params['httponly'],
                    'samesite' => 'Strict',
                ]
            );
        }

        session_destroy();
        static::$started = false;
    }

    // -------------------------------------------------------------------------
    // Internal guard
    // -------------------------------------------------------------------------

    private static function assertStarted(): void
    {
        if (!static::$started) {
            static::start();
        }
    }
}
