<?php

namespace App\Support;

/**
 * Parse/normalize exchange rate input (IDR per USD/JPY, etc.).
 * Supports European thousands (17.883 = 17883) and decimal (12,89 = 12.89).
 */
class ExchangeRateParser
{
    /** @return numeric-string */
    public static function canonicalString($value): string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return '0';
        }

        $isNegative = false;
        if (isset($raw[0]) && $raw[0] === '-') {
            $isNegative = true;
            $raw = trim(substr($raw, 1));
        } elseif (isset($raw[0]) && $raw[0] === '+') {
            $raw = trim(substr($raw, 1));
        }

        if ($raw === '' || $raw === '.') {
            return '0';
        }

        $lastComma = strrpos($raw, ',');
        $lastDot = strrpos($raw, '.');

        if ($lastComma !== false && ($lastDot === false || $lastComma > $lastDot)) {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        } else {
            $raw = str_replace(',', '', $raw);
            if (substr_count($raw, '.') > 1) {
                $raw = str_replace('.', '', $raw);
            } elseif (($dotPos = strrpos($raw, '.')) !== false) {
                $intRaw = substr($raw, 0, $dotPos);
                $frac = preg_replace('/\D/', '', substr($raw, $dotPos + 1));
                $intPart = str_replace('.', '', $intRaw);
                if (self::isThousandsDotNotation($intPart, $frac)) {
                    $raw = $intPart . $frac;
                } else {
                    $raw = ($intPart !== '' ? $intPart : '0') . ($frac !== '' ? '.' . $frac : '');
                }
            }
        }

        $raw = preg_replace('/[^0-9.]/', '', $raw);
        if ($raw === '' || $raw === '.') {
            return '0';
        }

        if ($isNegative && $raw !== '0') {
            return '-' . $raw;
        }

        return $raw;
    }

    public static function parseFloat($value): float
    {
        return (float) self::canonicalString($value);
    }

    /** DB storage format with 2 decimal places. */
    public static function normalizeForStorage($value): string
    {
        $num = self::parseFloat($value);

        return number_format($num, 2, '.', '');
    }

    private static function isThousandsDotNotation(string $intPart, string $frac): bool
    {
        return $intPart !== '' && strlen($frac) === 3 && ctype_digit($frac);
    }
}
