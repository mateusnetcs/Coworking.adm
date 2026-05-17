<?php

namespace App\Support;

class BrazilPhone
{
    /**
     * Normaliza para apenas dígitos (sem DDI).
     * Aceita entrada com ou sem 55 no início.
     */
    public static function digitsOnly(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '55') && strlen($digits) > 11) {
            $digits = substr($digits, 2);
        }

        return $digits;
    }

    public static function isValidMobile(string $phone): bool
    {
        $digits = self::digitsOnly($phone);

        if (strlen($digits) !== 11) {
            return false;
        }

        $ddd = (int) substr($digits, 0, 2);

        return $ddd >= 11 && $ddd <= 99 && $digits[2] === '9';
    }

    /** Formato E.164 para API do WhatsApp (Brasil). */
    public static function toE164(string $phone): string
    {
        return '+55'.self::digitsOnly($phone);
    }

    public static function formatDisplay(string $phone): string
    {
        $digits = self::digitsOnly($phone);

        if (strlen($digits) !== 11) {
            return $phone;
        }

        return sprintf(
            '(%s) %s-%s',
            substr($digits, 0, 2),
            substr($digits, 2, 5),
            substr($digits, 7, 4),
        );
    }
}
