<?php

namespace App\Support;

final class CashDrawer
{
    /**
     * @return array<int, array{denomination: int, count: int}>
     */
    public static function breakdown(int $changeInMinor): array
    {
        $rupees = intdiv($changeInMinor, Money::SCALE);
        $breakdown = [];

        foreach (config('inventory.cash_denominations') as $denomination) {
            $count = intdiv($rupees, $denomination);

            if ($count > 0) {
                $breakdown[] = ['denomination' => $denomination, 'count' => $count];
                $rupees -= $count * $denomination;
            }
        }

        return $breakdown;
    }
}
