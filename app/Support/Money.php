<?php

namespace App\Support;

/**
 * Every monetary calculation in this application runs on integer paise. Line
 * totals and tax are rounded once, at the point they are computed, and never
 * accumulated as floats.
 */
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

    /**
     * Tax on a line, rounded half-up to the nearest paise.
     */
    public static function percentageOf(int $minor, int|float|string $percentage): int
    {
        return (int) round($minor * ((float) $percentage) / 100);
    }
}
