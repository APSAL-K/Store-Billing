<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    public const REASON_SALE = 'sale';

    public const REASON_RESTOCK = 'restock';

    public const REASON_OPENING_BALANCE = 'opening_balance';

    protected $fillable = [
        'product_id',
        'order_id',
        'reason',
        'quantity_change',
        'balance_after',
    ];

    protected function casts(): array
    {
        return [
            'quantity_change' => 'integer',
            'balance_after' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
