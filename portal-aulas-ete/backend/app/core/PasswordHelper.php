<?php

class PasswordHelper
{
    public static function hash($plain)
    {
        if (function_exists('password_hash')) {
            return password_hash($plain, PASSWORD_BCRYPT);
        }

        return sha1($plain);
    }

    public static function verify($plain, $storedHash)
    {
        if ($storedHash === '' || $storedHash === null) {
            return false;
        }

        $storedHash = trim((string) $storedHash);
        $plain = (string) $plain;

        if (strpos($storedHash, '$2y$') === 0 || strpos($storedHash, '$2a$') === 0 || strpos($storedHash, '$2b$') === 0) {
            return function_exists('password_verify') ? password_verify($plain, $storedHash) : false;
        }

        return hash_equals($storedHash, sha1($plain));
    }
}
