<?php

namespace App\Support;

final class Money
{
    public const SCALE = 100;

    public static function toMinor(int|float|string $amount): int
    {
        return (int) round(((float) $amount) * self::SCALE);
    }

    public static function toDecimal(int $minor): string
    {
        return number_format($minor / self::SCALE, 2, '.', '');
    }

    public static function percentageOf(int $minor, int|float|string $percentage): int
    {
        return (int) round($minor * ((float) $percentage) / 100);
    }
}
