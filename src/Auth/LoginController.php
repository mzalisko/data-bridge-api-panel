<?php

declare(strict_types=1);

namespace App\Auth;

use App\Core\CSRF;
use App\Core\Database;
use App\Core\Logger;
use App\Core\Session;

/**
 * Handles the login page (GET) and login form submission (POST),
 * plus session logout (GET /logout).
 *
 * Security measures applied:
 *  - CSRF token validation on POST
 *  - Input sanitisation before DB query
 *  - Constant-time password comparison via password_verify()
 *  - Session ID regeneration on successful login
 *  - Rate limiting: max 5 failed attempts per IP per 15 minutes (session-based)
 *  - All events logged to the logs table
 */
class LoginController
{
    private const MAX_ATTEMPTS  = 5;
    private const LOCKOUT_SECS  = 900; // 15 minutes

    // -------------------------------------------------------------------------
    // GET /login
    // -------------------------------------------------------------------------

    public function showForm(): void
    {
        AuthGuard::redirectIfLoggedIn('/dashboard');

        $error  = Session::get('login_error', '');
        $locked = $this->isLockedOut();
        $token  = CSRF::getToken();

        // Clear flash error after reading
        Session::remove('login_error');

        $this->renderLoginPage($token, (string) $error, $locked);
    }

    // -------------------------------------------------------------------------
    // POST /login
    // -------------------------------------------------------------------------

    public function handleLogin(): void
    {
        // CSRF check
        if (!CSRF::validate($_POST['_csrf'] ?? '')) {
            Session::set('login_error', 'Invalid security token. Please try again.');
            header('Location: /login');
            exit;
        }

        // Rate limit
        if ($this->isLockedOut()) {
            Session::set('login_error', 'Too many failed attempts. Please wait 15 minutes.');
            header('Location: /login');
            exit;
        }

        // Sanitise inputs
        $email    = trim(filter_var($_POST['email']    ?? '', FILTER_SANITIZE_EMAIL));
        $password = trim($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            $this->recordFailedAttempt();
            Session::set('login_error', 'Email and password are required.');
            header('Location: /login');
            exit;
        }

        // Lookup user
        $user = $this->findActiveUserByEmail($email);

        if ($user === null || !password_verify($password, $user['password'])) {
            $this->recordFailedAttempt();
            (new Logger('auth'))->warning('login_failed', [
                'email' => $email,
                'ip'    => $this->getIp(),
            ]);
            // Deliberate vague message — do not reveal which part was wrong
            Session::set('login_error', 'Invalid email or password.');
            header('Location: /login');
            exit;
        }

        // Successful login
        $this->clearFailedAttempts();
        Session::regenerate();
        Session::set('user_id',   (int) $user['id']);
        Session::set('user_name', $user['name']);
        Session::set('user_role', $user['role']);
        CSRF::regenerate();

        (new Logger('auth'))->info('login_success', [
            'user_id' => $user['id'],
            'ip'      => $this->getIp(),
        ]);

        header('Location: /dashboard');
        exit;
    }

    // -------------------------------------------------------------------------
    // GET /logout
    // -------------------------------------------------------------------------

    public function logout(): void
    {
        if (Session::has('user_id')) {
            (new Logger('auth'))->info('logout', [
                'user_id' => Session::get('user_id'),
                'ip'      => $this->getIp(),
            ]);
        }

        Session::destroy();
        header('Location: /login');
        exit;
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    private function findActiveUserByEmail(string $email): ?array
    {
        $pdo  = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare(
            'SELECT id, name, email, password, role FROM users WHERE email = ? AND is_active = 1 LIMIT 1'
        );
        $stmt->execute([$email]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    private function isLockedOut(): bool
    {
        $attempts  = (int)   Session::get('login_attempts', 0);
        $lastTime  = (int)   Session::get('login_last_attempt', 0);

        if ($attempts < self::MAX_ATTEMPTS) {
            return false;
        }

        if ((time() - $lastTime) >= self::LOCKOUT_SECS) {
            $this->clearFailedAttempts();
            return false;
        }

        return true;
    }

    private function recordFailedAttempt(): void
    {
        $attempts = (int) Session::get('login_attempts', 0);
        Session::set('login_attempts',     $attempts + 1);
        Session::set('login_last_attempt', time());
    }

    private function clearFailedAttempts(): void
    {
        Session::remove('login_attempts');
        Session::remove('login_last_attempt');
    }

    private function getIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    // -------------------------------------------------------------------------
    // View
    // -------------------------------------------------------------------------

    private function renderLoginPage(string $token, string $error, bool $locked): void
    {
        $errorHtml = '';
        if ($locked) {
            $errorHtml = '<p class="login-alert login-alert--error">Too many failed attempts. Please wait 15 minutes.</p>';
        } elseif ($error !== '') {
            $errorHtml = '<p class="login-alert login-alert--error">' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '</p>';
        }

        $disabled = $locked ? 'disabled' : '';

        header('Content-Type: text/html; charset=UTF-8');
        echo <<<HTML
        <!DOCTYPE html>
        <html lang="uk">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Login — DataBridgeApi</title>
            <link rel="stylesheet" href="/assets/css/app.css">
        </head>
        <body class="login-body">
            <main class="login-wrap">
                <div class="login-card">
                    <div class="login-logo">
                        <span class="login-logo__icon">&#9670;</span>
                        <span class="login-logo__name">DataBridgeApi</span>
                    </div>

                    <h1 class="login-title">Sign in</h1>

                    {$errorHtml}

                    <form method="POST" action="/login" class="login-form" novalidate>
                        <input type="hidden" name="_csrf" value="{$token}">

                        <div class="form-group">
                            <label class="form-label" for="email">Email</label>
                            <input
                                class="form-input"
                                type="email"
                                id="email"
                                name="email"
                                autocomplete="username"
                                autofocus
                                required
                                {$disabled}
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="password">Password</label>
                            <input
                                class="form-input"
                                type="password"
                                id="password"
                                name="password"
                                autocomplete="current-password"
                                required
                                {$disabled}
                            >
                        </div>

                        <button class="btn btn--primary btn--full" type="submit" {$disabled}>
                            Sign in
                        </button>
                    </form>
                </div>
            </main>
        </body>
        </html>
        HTML;
    }
}
