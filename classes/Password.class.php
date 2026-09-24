<?php

/**
 * Password hashing helpers (replaces the old BCrypt class).
 *
 * Hashes created by the original game were crypt('$2a$') hashes of htmlentities($password); they
 * are still accepted, and LRSys::login() upgrades them to password_hash() on the next login.
 */
final class Password {

    const MIN_LENGTH = 8;

    public static function hash(string $password): string {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public static function verify($password, $hash): bool {
        if (!is_string($password) || !is_string($hash) || $hash === '') {
            return false;
        }
        if (password_verify($password, $hash)) {
            return true;
        }
        return self::isLegacy($hash) && password_verify(htmlentities($password, ENT_COMPAT, 'UTF-8'), $hash);
    }

    public static function needsRehash(string $hash): bool {
        return self::isLegacy($hash) || password_needs_rehash($hash, PASSWORD_DEFAULT);
    }

    private static function isLegacy(string $hash): bool {
        return strpos($hash, '$2a$') === 0;
    }

}
