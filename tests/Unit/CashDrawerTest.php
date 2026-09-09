<?php

namespace Tests\Unit;

use App\Support\CashDrawer;
use Tests\TestCase;

class CashDrawerTest extends TestCase
{
    public function test_it_returns_the_largest_notes_first(): void
    {
        $this->assertSame([
            ['denomination' => 500, 'count' => 1],
            ['denomination' => 200, 'count' => 1],
            ['denomination' => 20, 'count' => 1],
            ['denomination' => 2, 'count' => 1],
        ], CashDrawer::breakdown(72200));
    }

    public function test_it_returns_nothing_when_no_change_is_owed(): void
    {
        $this->assertSame([], CashDrawer::breakdown(0));
    }

    public function test_it_ignores_paise_the_drawer_cannot_hand_back(): void
    {
        // 22.80 rounds down to notes and coins worth 22; the exact figure is
        // still what the order stores and prints.
        $this->assertSame([
            ['denomination' => 20, 'count' => 1],
            ['denomination' => 2, 'count' => 1],
        ], CashDrawer::breakdown(2280));
    }
}
