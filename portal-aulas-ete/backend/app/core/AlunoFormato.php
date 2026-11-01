<?php

/**
 * Normaliza celular e e-mail na edição do aluno.
 * Celular canônico (11 dígitos): (DD)NNNNN-NNNN, ex. (81)99794-1373.
 * E-mail: minúsculas, sem espaços, um @, sem caracteres fora do uso escolar comum.
 */
class AlunoFormato
{
    public static function digitos($value)
    {
        $digits = preg_replace('/\D+/', '', (string) $value);
        if ($digits === null) {
            return '';
        }

        return $digits;
    }

    /**
     * 11 dígitos viram (DD)NNNNN-NNNN. Outra quantidade permanece só com dígitos
     * (não inventa máscara e cabe em usuarios.celular VARCHAR(20)).
     */
    public static function celular($value)
    {
        $digits = self::digitos($value);
        if (strlen($digits) > 11) {
            $digits = substr($digits, 0, 11);
        }

        if (strlen($digits) !== 11) {
            return $digits;
        }

        return '(' . substr($digits, 0, 2) . ')' . substr($digits, 2, 5) . '-' . substr($digits, 7, 4);
    }

    public static function email($value)
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/\s+/', '', $value);
        if ($value === null) {
            $value = '';
        }

        $clean = '';
        $hasAt = false;
        $length = strlen($value);

        for ($i = 0; $i < $length; $i++) {
            $ch = $value[$i];
            if ($ch === '@') {
                if ($hasAt || $clean === '') {
                    continue;
                }
                $hasAt = true;
                $clean .= $ch;
                continue;
            }

            if ($hasAt) {
                if (preg_match('/^[a-z0-9.\-]$/', $ch)) {
                    $clean .= $ch;
                }
                continue;
            }

            if (preg_match('/^[a-z0-9._\-]$/', $ch)) {
                $clean .= $ch;
            }
        }

        return $clean;
    }
}
