<?php

namespace App\Support;

use InvalidArgumentException;

final class MoneyAmount
{
    private const PATTERN = '/^(-?)(\d{1,12})(?:[,.](\d{1,2}))?$/D';

    public static function toMinor(string $value): int
    {
        if (! preg_match(self::PATTERN, $value, $matches)) {
            throw new InvalidArgumentException('The amount must be a decimal string with at most two fraction digits.');
        }

        $minor = ((int) $matches[2] * 100) + (int) str_pad($matches[3] ?? '', 2, '0');

        return $matches[1] === '-' ? -$minor : $minor;
    }

    public static function formatInput(int $minor): string
    {
        $sign = $minor < 0 ? '-' : '';
        $absolute = abs($minor);

        return $sign.intdiv($absolute, 100).','.str_pad((string) ($absolute % 100), 2, '0');
    }
}
