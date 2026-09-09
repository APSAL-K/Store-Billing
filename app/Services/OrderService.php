<?php

namespace App\Services;

use App\Data\NewOrderData;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\OrderNotEditableException;
use App\Jobs\SendOrderConfirmation;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use App\Support\Money;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(private readonly OrderTotals $totals) {}

    /**
     * @throws InsufficientStockException
     * @throws ValidationException
     */
    public function place(NewOrderData $data): Order
    {
        $order = DB::transaction(function () use ($data): Order {
            $customer = $this->resolveCustomer($data);
            $products = $this->lockProducts($data->lines->pluck('productId')->all());

            $priced = $this->totals->for($data, $products);
            $tenderedMinor = $this->tendered($data, $priced);

            $order = Order::create([
                'customer_id' => $customer->id,
                'subtotal' => Money::toDecimal($priced->subtotalMinor),
                'tax_total' => Money::toDecimal($priced->taxTotalMinor),
                'grand_total' => Money::toDecimal($priced->grandTotalMinor),
                'amount_tendered' => $tenderedMinor === null ? null : Money::toDecimal($tenderedMinor),
                'change_due' => $tenderedMinor === null
                    ? null
                    : Money::toDecimal($tenderedMinor - $priced->grandTotalMinor),
                'placed_at' => now(),
            ]);

            $order->items()->createMany($priced->items);

            $deltas = $data->lines->mapWithKeys(fn ($line) => [$line->productId => -$line->quantity])->all();
            $this->applyStockDeltas($deltas, $products, $order, StockMovement::REASON_SALE);

            return $order;
        });

        SendOrderConfirmation::dispatch($order->id)->afterCommit();

        return $order->load(['customer', 'items.product']);
    }

    /**
     * @throws InsufficientStockException
     * @throws OrderNotEditableException
     * @throws ValidationException
     */
    public function update(Order $order, NewOrderData $data): Order
    {
        if ($order->isVoided()) {
            throw new OrderNotEditableException('A voided bill cannot be edited.');
        }

        DB::transaction(function () use ($order, $data): void {
            $customer = $this->resolveCustomer($data);

            $previous = $order->items()->pluck('quantity', 'product_id');
            $requested = $data->lines->mapWithKeys(fn ($line) => [$line->productId => $line->quantity]);

            $products = $this->lockProducts(
                $previous->keys()->merge($requested->keys())->unique()->all()
            );

            $priced = $this->totals->for($data, $products);
            $tenderedMinor = $this->tendered($data, $priced);

            $deltas = $previous->keys()
                ->merge($requested->keys())
                ->unique()
                ->mapWithKeys(fn (int $productId) => [
                    $productId => (int) $previous->get($productId, 0) - (int) $requested->get($productId, 0),
                ])
                ->reject(fn (int $delta): bool => $delta === 0)
                ->all();

            $this->applyStockDeltas($deltas, $products, $order, StockMovement::REASON_ADJUSTMENT);

            $order->items()->delete();
            $order->items()->createMany($priced->items);

            $order->update([
                'customer_id' => $customer->id,
                'subtotal' => Money::toDecimal($priced->subtotalMinor),
                'tax_total' => Money::toDecimal($priced->taxTotalMinor),
                'grand_total' => Money::toDecimal($priced->grandTotalMinor),
                'amount_tendered' => $tenderedMinor === null ? null : Money::toDecimal($tenderedMinor),
                'change_due' => $tenderedMinor === null
                    ? null
                    : Money::toDecimal($tenderedMinor - $priced->grandTotalMinor),
            ]);
        });

        SendOrderConfirmation::dispatch($order->id)->afterCommit();

        return $order->fresh(['customer', 'items.product']);
    }

    /**
     * @throws OrderNotEditableException
     */
    public function void(Order $order, ?string $reason = null): Order
    {
        if ($order->isVoided()) {
            throw new OrderNotEditableException('This bill has already been voided.');
        }

        DB::transaction(function () use ($order, $reason): void {
            $lines = $order->items()->pluck('quantity', 'product_id');
            $products = $this->lockProducts($lines->keys()->all());

            $deltas = $lines->map(fn (int $quantity): int => $quantity)->all();

            $this->applyStockDeltas($deltas, $products, $order, StockMovement::REASON_VOID, $reason);

            $order->update(['void_reason' => $reason]);
            $order->delete();
        });

        return $order->fresh(['customer', 'items.product']);
    }

    private function resolveCustomer(NewOrderData $data): Customer
    {
        $customer = Customer::firstOrNew(['email' => $data->customerEmail]);

        if ($data->customerName !== null && $data->customerName !== '') {
            $customer->name = $data->customerName;
        }

        $customer->save();

        return $customer;
    }

    /**
     * @param  array<int, int>  $productIds
     * @return EloquentCollection<int, Product>
     */
    private function lockProducts(array $productIds): EloquentCollection
    {
        return Product::query()
            ->whereIn('id', $productIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    /**
     * @param  array<int, int>  $deltas  Positive returns stock to the shelf, negative takes it off.
     * @param  EloquentCollection<int, Product>  $products
     *
     * @throws InsufficientStockException
     */
    private function applyStockDeltas(
        array $deltas,
        EloquentCollection $products,
        Order $order,
        string $reason,
        ?string $note = null,
    ): void {
        $shortages = [];

        foreach ($deltas as $productId => $delta) {
            $product = $products[$productId];

            if ($delta < 0 && $product->stock_on_hand < -$delta) {
                $shortages[] = [
                    'product_id' => $product->id,
                    'code' => $product->code,
                    'name' => $product->name,
                    'requested' => -$delta,
                    'available' => $product->stock_on_hand,
                ];
            }
        }

        if ($shortages !== []) {
            throw new InsufficientStockException($shortages);
        }

        foreach ($deltas as $productId => $delta) {
            $product = $products[$productId];

            $updated = $delta < 0
                ? Product::whereKey($productId)
                    ->where('stock_on_hand', '>=', -$delta)
                    ->decrement('stock_on_hand', -$delta)
                : Product::whereKey($productId)->increment('stock_on_hand', $delta);

            if ($updated === 0) {
                throw new InsufficientStockException([[
                    'product_id' => $product->id,
                    'code' => $product->code,
                    'name' => $product->name,
                    'requested' => -$delta,
                    'available' => $product->refresh()->stock_on_hand,
                ]]);
            }

            StockMovement::create([
                'product_id' => $productId,
                'order_id' => $order->id,
                'reason' => $reason,
                'note' => $note,
                'quantity_change' => $delta,
                'balance_after' => $product->stock_on_hand + $delta,
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    private function tendered(NewOrderData $data, PricedOrder $priced): ?int
    {
        if ($data->amountTendered === null) {
            return null;
        }

        $tenderedMinor = Money::toMinor($data->amountTendered);

        if ($tenderedMinor < $priced->grandTotalMinor) {
            throw ValidationException::withMessages([
                'amount_tendered' => sprintf(
                    'The amount given is short of the bill total of %s.',
                    Money::toDecimal($priced->grandTotalMinor)
                ),
            ]);
        }

        return $tenderedMinor;
    }
}
