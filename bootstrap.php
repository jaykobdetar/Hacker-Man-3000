<?php

/**
 * Loaded first by every entry point (web pages, AJAX endpoints and cron scripts), via
 * classes/PDO.class.php or directly.
 *
 * - defines BASE_PATH (the repository root) so nothing depends on /var/www
 * - loads Composer dependencies and the .env configuration
 * - configures error reporting, the session cookie and security headers
 * - enforces CSRF protection on POST requests
 */

if (defined('BASE_PATH')) {
    return;
}

define('BASE_PATH', __DIR__);

if (!is_file(BASE_PATH . '/vendor/autoload.php')) {
    http_response_code(500);
    exit("Dependencies are missing. Run `composer install` in " . BASE_PATH . ".\n");
}

require BASE_PATH . '/vendor/autoload.php';
require_once BASE_PATH . '/classes/Config.class.php';
require_once BASE_PATH . '/classes/SqlQuery.class.php';
require_once BASE_PATH . '/classes/Csrf.class.php';
require_once BASE_PATH . '/classes/GeneratedPage.class.php';

Config::load(BASE_PATH);

date_default_timezone_set(Config::get('APP_TIMEZONE', 'UTC'));

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', Config::bool('APP_DEBUG') ? '1' : '0');
ini_set('log_errors', '1');

if (PHP_SAPI !== 'cli') {

    // Session cookie hardening. SameSite=Strict also stops cross-site GET links from triggering
    // in-game actions, since the browser will not send the session cookie with them.
    $secure = Config::bool('SESSION_SECURE_COOKIE', Config::isHttps());
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => Config::get('SESSION_SAMESITE', 'Strict'),
    ]);

    if (!headers_sent()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: same-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
        header('Cross-Origin-Opener-Policy: same-origin');
        // The legacy pages rely on inline scripts and styles, so 'unsafe-inline' is still needed;
        // the policy still blocks third-party scripts, plugins, framing and form hijacking.
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; "
            . "style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' data:; "
            . "connect-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'");
        if ($secure) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
        header_remove('X-Powered-By');
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    Csrf::protect();
}

/** HTML-escapes a value for output in element content or a quoted attribute. */
function esc($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
