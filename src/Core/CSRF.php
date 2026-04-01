<?php

declare(strict_types=1);

namespace App\Core;

/**
 * CSRF token generator and validator.
 *
 * Tokens are stored in the server-side session under '_csrf_token'.
 * Each token is a 32-byte cryptographically random hex string.
 * Tokens are single-use: validated tokens are immediately regenerated
 * to prevent replay attacks.
 *
 * Usage — in a form:
 *   $token = CSRF::getToken();
 *   echo '<input type="hidden" name="_csrf" value="' . htmlspecialchars($token) . '">';
 *
 * Usage — on form submission:
 *   if (!CSRF::validate($_POST['_csrf'] ?? '')) {
 *       // reject request
 *   }
 */
class CSRF
{
    private const SESSION_KEY = '_csrf_token';
    private const TOKEN_BYTES = 32;

    /**
     * Return the current CSRF token, generating one if it does not exist yet.
     */
    public static function getToken(): string
    {
        Session::start();

        if (!Session::has(self::SESSION_KEY)) {
            Session::set(self::SESSION_KEY, self::generate());
        }

        return (string) Session::get(self::SESSION_KEY);
    }

    /**
     * Validate a submitted token against the session token.
     *
     * Uses hash_equals() to prevent timing-based attacks.
     * On success the token is rotated (single-use).
     *
     * @param string $submittedToken The value from the request (_csrf field or header)
     */
    public static function validate(string $submittedToken): bool
    {
        Session::start();

        $storedToken = Session::get(self::SESSION_KEY, '');

        if (!is_string($storedToken) || $storedToken === '') {
            return false;
        }

        $valid = hash_equals($storedToken, $submittedToken);

        // Rotate unconditionally — an attacker learning the old token gains nothing
        Session::set(self::SESSION_KEY, self::generate());

        return $valid;
    }

    /**
     * Force-regenerate the token (e.g. after login / privilege change).
     */
    public static function regenerate(): string
    {
        Session::start();
        $token = self::generate();
        Session::set(self::SESSION_KEY, $token);
        return $token;
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    private static function generate(): string
    {
        return bin2hex(random_bytes(self::TOKEN_BYTES));
    }
}
