<?php

class PasswordHelper
{
    public static function resetPassword()
    {
        return '@ETE2626';
    }

    public static function isResetPassword($plain)
    {
        return (string) $plain === self::resetPassword();
    }

    public static function storedIsResetPassword($storedHash)
    {
        return self::verify(self::resetPassword(), $storedHash);
    }

    public static function novaSenhaErro($senha, $confirmacao)
    {
        if ($senha === '' || $confirmacao === '') {
            return 'Informe a nova senha e a confirmação.';
        }

        if (strlen($senha) < 6) {
            return 'A senha deve ter pelo menos 6 caracteres.';
        }

        if ($senha !== $confirmacao) {
            return 'A confirmação de senha não confere.';
        }

        if (self::isResetPassword($senha)) {
            return 'Escolha uma senha diferente da senha provisória.';
        }

        return '';
    }

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
