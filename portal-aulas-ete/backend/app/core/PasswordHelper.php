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

        if (strpos($storedHash, '$2y$') === 0 || strpos($storedHash, '$2a$') === 0) {
            return password_verify($plain, $storedHash);
        }

        return sha1($plain) === $storedHash;
    }
}
