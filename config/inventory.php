<?php

return [

    'low_stock_threshold' => (int) env('INVENTORY_LOW_STOCK_THRESHOLD', 10),

    'cash_denominations' => [500, 200, 100, 50, 20, 10, 5, 2, 1],

];
