<?php

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory, SoftDeletes;

    protected $appends = [
        'reference',
    ];

    protected $fillable = [
        'customer_id',
        'subtotal',
        'tax_total',
        'grand_total',
        'amount_tendered',
        'change_due',
        'placed_at',
        'void_reason',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'amount_tendered' => 'decimal:2',
            'change_due' => 'decimal:2',
            'placed_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    protected function reference(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->id === null
            ? null
            : sprintf('ORD-%s-%05d', $this->placed_at?->format('Ymd') ?? now()->format('Ymd'), $this->id));
    }

    public function isVoided(): bool
    {
        return $this->deleted_at !== null;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
