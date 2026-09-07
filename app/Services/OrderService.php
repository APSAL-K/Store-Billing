<?php

namespace App\Services;

use App\Data\NewOrderData;
use App\Exceptions\InsufficientStockException;
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
     * Record a counter sale: reserve the stock, price the lines, persist the
     * order, and hand the confirmation email off to the queue.
     *
     * @throws InsufficientStockException
     */
    public function place(NewOrderData $data): Order
    {
        $order = DB::transaction(function () use ($data): Order {
            $customer = $this->resolveCustomer($data);
            $products = $this->lockProducts($data);

            $this->guardAgainstShortages($data, $products);

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

            $this->deductStock($order, $data, $products);

            return $order;
        });

        SendOrderConfirmation::dispatch($order->id)->afterCommit();

        return $order->load(['customer', 'items.product']);
    }

    /**
     * Cash handed over must at least cover the bill.
     *
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

    private function resolveCustomer(NewOrderData $data): Customer
    {
        $customer = Customer::firstOrNew(['email' => $data->customerEmail]);

        // A returning customer keeps the name we already have on file unless the
        // counter deliberately typed a new one.
        if ($data->customerName !== null && $data->customerName !== '') {
            $customer->name = $data->customerName;
        }

        $customer->save();

        return $customer;
    }

    /**
     * Take a row lock on every product in the order, in a stable id order so two
     * overlapping orders can never deadlock against each other.
     *
     * @return EloquentCollection<int, Product>
     */
    private function lockProducts(NewOrderData $data): EloquentCollection
    {
        return Product::query()
            ->whereIn('id', $data->lines->pluck('productId'))
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    /**
     * @param  EloquentCollection<int, Product>  $products
     *
     * @throws InsufficientStockException
     */
    private function guardAgainstShortages(NewOrderData $data, EloquentCollection $products): void
    {
        $shortages = $data->lines
            ->filter(fn ($line) => $products[$line->productId]->stock_on_hand < $line->quantity)
            ->map(fn ($line) => [
                'product_id' => $line->productId,
                'code' => $products[$line->productId]->code,
                'name' => $products[$line->productId]->name,
                'requested' => $line->quantity,
                'available' => $products[$line->productId]->stock_on_hand,
            ])
            ->values()
            ->all();

        if ($shortages !== []) {
            throw new InsufficientStockException($shortages);
        }
    }

    /**
     * @param  EloquentCollection<int, Product>  $products
     *
     * @throws InsufficientStockException
     */
    private function deductStock(Order $order, NewOrderData $data, EloquentCollection $products): void
    {
        foreach ($data->lines as $line) {
            // The row lock above already serialises overlapping orders. The
            // stock_on_hand condition is a second line of defence: on a driver
            // without row locking the decrement still refuses to go negative,
            // and we surface that as a shortage rather than overselling.
            $updated = Product::whereKey($line->productId)
                ->where('stock_on_hand', '>=', $line->quantity)
                ->decrement('stock_on_hand', $line->quantity);

            if ($updated === 0) {
                $product = $products[$line->productId];

                throw new InsufficientStockException([[
                    'product_id' => $product->id,
                    'code' => $product->code,
                    'name' => $product->name,
                    'requested' => $line->quantity,
                    'available' => $product->refresh()->stock_on_hand,
                ]]);
            }

            StockMovement::create([
                'product_id' => $line->productId,
                'order_id' => $order->id,
                'reason' => StockMovement::REASON_SALE,
                'quantity_change' => -$line->quantity,
                'balance_after' => $products[$line->productId]->stock_on_hand - $line->quantity,
            ]);
        }
    }
}
