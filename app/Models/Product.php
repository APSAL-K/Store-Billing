<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'unit_price',
        'tax_percentage',
        'stock_on_hand',
        'low_stock_threshold',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'tax_percentage' => 'decimal:2',
            'stock_on_hand' => 'integer',
            'low_stock_threshold' => 'integer',
        ];
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function effectiveLowStockThreshold(?int $override = null): int
    {
        return $override ?? $this->low_stock_threshold ?? config('inventory.low_stock_threshold');
    }

    public function scopeLowOnStock(Builder $query, ?int $threshold = null): Builder
    {
        if ($threshold !== null) {
            return $query->where('stock_on_hand', '<=', $threshold);
        }

        $default = (int) config('inventory.low_stock_threshold');

        return $query->whereRaw('stock_on_hand <= COALESCE(low_stock_threshold, ?)', [$default]);
    }
}
