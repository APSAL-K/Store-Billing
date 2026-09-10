<?php

namespace App\Console\Commands;

use App\Data\NewOrderData;
use App\Data\OrderLineData;
use App\Exceptions\InsufficientStockException;
use App\Services\OrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlaceOrder extends Command
{
    protected $signature = 'orders:place
        {--email= : Customer email}
        {--name= : Customer name, required the first time an email is seen}
        {--item=* : One or more lines as product_id:quantity}
        {--tendered= : Cash handed over by the customer}
        {--connection= : Database connection to run against}';

    protected $description = 'Record a counter sale without going through the HTTP layer';

    public const EXIT_SHORT_ON_STOCK = 4;

    public const EXIT_INVALID = 5;

    public function handle(OrderService $orders): int
    {
        if ($connection = $this->option('connection')) {
            DB::setDefaultConnection($connection);
        }

        $lines = collect($this->option('item'))->map(function (string $item): OrderLineData {
            [$productId, $quantity] = array_pad(explode(':', $item, 2), 2, '1');

            return new OrderLineData((int) $productId, (int) $quantity);
        });

        if ($lines->isEmpty() || ! $this->option('email')) {
            $this->error('An email and at least one --item=product_id:quantity are required.');

            return self::FAILURE;
        }

        try {
            $order = $orders->place(new NewOrderData(
                customerEmail: (string) $this->option('email'),
                customerName: $this->option('name'),
                lines: $lines,
                amountTendered: $this->option('tendered') === null ? null : (float) $this->option('tendered'),
            ));
        } catch (ValidationException $e) {
            $this->output->writeln(json_encode([
                'status' => 'rejected',
                'errors' => $e->errors(),
            ], JSON_THROW_ON_ERROR));

            return self::EXIT_INVALID;
        } catch (InsufficientStockException $e) {
            $this->line(json_encode(['status' => 'short', 'shortages' => $e->shortages]));

            return self::EXIT_SHORT_ON_STOCK;
        }

        $this->line(json_encode([
            'status' => 'placed',
            'order_id' => $order->id,
            'reference' => $order->reference,
            'grand_total' => $order->grand_total,
        ]));

        return self::SUCCESS;
    }
}
