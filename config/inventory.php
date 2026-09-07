<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Low Stock Threshold
    |--------------------------------------------------------------------------
    |
    | Default number of units at or below which a product is considered low on
    | stock. Individual products may override this via their own
    | "low_stock_threshold" column, and the low-stock endpoint accepts a
    | "threshold" parameter that overrides both for a single request.
    |
    */

    'low_stock_threshold' => (int) env('INVENTORY_LOW_STOCK_THRESHOLD', 10),

    /*
    |--------------------------------------------------------------------------
    | Cash Denominations
    |--------------------------------------------------------------------------
    |
    | Notes and coins the counter can hand back, largest first. Used to break
    | the change due into a denomination list for the cashier.
    |
    */

    'cash_denominations' => [500, 200, 100, 50, 20, 10, 5, 2, 1],

];
