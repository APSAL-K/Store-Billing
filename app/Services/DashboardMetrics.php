<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardMetrics
{
    /**
     * @return array<string, mixed>
     */
    public function headline(): array
    {
        $today = $this->tradeOn(today());
        $yesterday = $this->tradeOn(today()->subDay());

        return [
            'today' => $today,
            'yesterday' => $yesterday,
            'change' => [
                'revenue' => $this->percentChange($yesterday['revenue'], $today['revenue']),
                'orders' => $this->percentChange($yesterday['orders'], $today['orders']),
                'units' => $this->percentChange($yesterday['units'], $today['units']),
                'average' => $this->percentChange($yesterday['average'], $today['average']),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function allTime(): array
    {
        $revenue = (float) Order::sum('grand_total');
        $orders = Order::count();

        return [
            'orders' => $orders,
            'deleted' => Order::onlyTrashed()->count(),
            'revenue' => $revenue,
            'tax' => (float) Order::sum('tax_total'),
            'customers' => Customer::count(),
            'average' => $orders > 0 ? $revenue / $orders : 0.0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function inventoryValue(): array
    {
        $products = Product::all();

        return [
            'products' => $products->count(),
            'units' => (int) $products->sum('stock_on_hand'),
            'retail' => (float) $products->sum(
                fn (Product $product): float => (float) $product->unit_price * $product->stock_on_hand
            ),
            'low' => $products->filter(
                fn (Product $product): bool => $product->stock_on_hand <= $product->effectiveLowStockThreshold()
            )->count(),
            'out' => $products->where('stock_on_hand', 0)->count(),
        ];
    }

    /**
     * Revenue per day for the trailing window, including days with no trade so
     * the chart keeps an even x axis.
     *
     * @return Collection<int, array{date: Carbon, revenue: float, orders: int}>
     */
    public function revenueTrend(int $days = 14): Collection
    {
        $since = today()->subDays($days - 1);

        $day = $this->dateExpression('placed_at');

        $rows = Order::query()
            ->where('placed_at', '>=', $since)
            ->selectRaw("{$day} as day, SUM(grand_total) as revenue, COUNT(*) as orders")
            ->groupBy('day')
            ->get()
            ->keyBy(fn ($row): string => (string) $row->day);

        return collect(range($days - 1, 0))->map(function (int $ago) use ($rows): array {
            $date = today()->subDays($ago);
            $row = $rows->get($date->toDateString());

            return [
                'date' => $date,
                'revenue' => (float) ($row->revenue ?? 0),
                'orders' => (int) ($row->orders ?? 0),
            ];
        });
    }

    /**
     * @return Collection<int, Product>
     */
    public function bestSellers(int $limit = 5): Collection
    {
        return Product::query()
            ->withSum(['orderItems as units_sold' => fn ($query) => $query->whereHas('order')], 'quantity')
            ->withSum(['orderItems as revenue' => fn ($query) => $query->whereHas('order')], 'line_total')
            ->orderByDesc('units_sold')
            ->limit($limit)
            ->get()
            ->filter(fn (Product $product): bool => (int) $product->units_sold > 0)
            ->values();
    }

    /**
     * @return Collection<int, Customer>
     */
    public function topCustomers(int $limit = 5): Collection
    {
        return Customer::query()
            ->withCount('orders')
            ->withSum('orders as lifetime_value', 'grand_total')
            ->orderByDesc('lifetime_value')
            ->limit($limit)
            ->get()
            ->filter(fn (Customer $customer): bool => (float) $customer->lifetime_value > 0)
            ->values();
    }

    /**
     * @return Collection<int, Order>
     */
    public function recentOrders(int $limit = 6): Collection
    {
        return Order::with('customer')->latest('placed_at')->latest('id')->limit($limit)->get();
    }

    /**
     * @return Collection<int, Product>
     */
    public function lowStock(int $limit = 6): Collection
    {
        return Product::lowOnStock()->orderBy('stock_on_hand')->limit($limit)->get();
    }

    /**
     * @return Collection<int, StockMovement>
     */
    public function recentMovements(int $limit = 8): Collection
    {
        return StockMovement::with(['product', 'order'])->latest('id')->limit($limit)->get();
    }

    /**
     * Which hours the counter is busiest, so staffing has something to look at.
     *
     * @return Collection<int, array{hour: int, revenue: float, orders: int}>
     */
    public function tradeByHour(int $days = 30): Collection
    {
        $hour = $this->hourExpression('placed_at');

        $rows = Order::query()
            ->where('placed_at', '>=', today()->subDays($days - 1))
            ->selectRaw("{$hour} as hour, SUM(grand_total) as revenue, COUNT(*) as orders")
            ->groupBy('hour')
            ->get()
            ->keyBy(fn ($row): int => (int) $row->hour);

        return collect(range(0, 23))
            ->map(fn (int $hour): array => [
                'hour' => $hour,
                'revenue' => (float) ($rows->get($hour)->revenue ?? 0),
                'orders' => (int) ($rows->get($hour)->orders ?? 0),
            ])
            ->filter(fn (array $slot): bool => $slot['hour'] >= 6 && $slot['hour'] <= 22)
            ->values();
    }

    private function dateExpression(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m-%d', {$column})",
            'pgsql' => "to_char({$column}, 'YYYY-MM-DD')",
            default => "DATE({$column})",
        };
    }

    private function hourExpression(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "CAST(strftime('%H', {$column}) AS INTEGER)",
            'pgsql' => "EXTRACT(HOUR FROM {$column})",
            default => "HOUR({$column})",
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function tradeOn(Carbon $date): array
    {
        $orders = Order::whereDate('placed_at', $date);
        $count = (clone $orders)->count();
        $revenue = (float) (clone $orders)->sum('grand_total');

        return [
            'orders' => $count,
            'revenue' => $revenue,
            'tax' => (float) (clone $orders)->sum('tax_total'),
            'units' => (int) OrderItem::whereIn('order_id', (clone $orders)->select('id'))->sum('quantity'),
            'average' => $count > 0 ? $revenue / $count : 0.0,
        ];
    }

    private function percentChange(float|int $before, float|int $after): ?float
    {
        if ((float) $before === 0.0) {
            return null;
        }

        return (($after - $before) / $before) * 100;
    }
}
