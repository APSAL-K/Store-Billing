<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    public const REASON_SALE = 'sale';

    public const REASON_RESTOCK = 'restock';

    public const REASON_OPENING_BALANCE = 'opening_balance';

    public const REASON_VOID = 'void';

    public const REASON_ADJUSTMENT = 'adjustment';

    public const LABELS = [
        self::REASON_SALE => 'Sale',
        self::REASON_RESTOCK => 'Restock',
        self::REASON_OPENING_BALANCE => 'Opening balance',
        self::REASON_VOID => 'Bill voided',
        self::REASON_ADJUSTMENT => 'Bill edited',
    ];

    protected $fillable = [
        'product_id',
        'order_id',
        'reason',
        'note',
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

    public function label(): string
    {
        return self::LABELS[$this->reason] ?? ucfirst(str_replace('_', ' ', $this->reason));
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class)->withTrashed();
    }
}
