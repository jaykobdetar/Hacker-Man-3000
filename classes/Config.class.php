<?php

/**
 * Runtime configuration, read from environment variables and the optional .env file in the
 * repository root (see .env.example). Secrets must never be hard-coded in the source.
 */
final class Config {

    private static $loaded = false;

    /** Values from .env. Kept private instead of being copied into $_SERVER/$_ENV/getenv(), so
     *  secrets can't leak through code that dumps those (debug output, bug reports, phpinfo). */
    private static $values = [];

    public static function load(string $basePath): void {
        if (self::$loaded) {
            return;
        }
        self::$loaded = true;
        if (is_file($basePath . '/.env')) {
            self::$values = Dotenv\Dotenv::createArrayBacked($basePath)->safeLoad();
        }
    }

    public static function get(string $key, $default = null) {
        // Real environment variables win over .env.
        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }
        if (array_key_exists($key, self::$values)) {
            return self::$values[$key];
        }
        return $default;
    }

    /** Like get(), but fails loudly when a required setting is missing. */
    public static function require(string $key): string {
        $value = self::get($key);
        if ($value === null || $value === '') {
            throw new RuntimeException("Missing required configuration value $key (see .env.example).");
        }
        return (string) $value;
    }

    public static function bool(string $key, bool $default = false): bool {
        $value = self::get($key);
        if ($value === null || $value === '') {
            return $default;
        }
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function isHttps(): bool {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }
        // Only trust X-Forwarded-Proto when running behind a known reverse proxy.
        return self::bool('TRUST_PROXY_HEADERS')
            && isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
            && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https';
    }

    /** Client IP address. Proxy headers are only honoured when TRUST_PROXY_HEADERS is enabled. */
    public static function clientIp(): string {
        if (self::bool('TRUST_PROXY_HEADERS') && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

}
