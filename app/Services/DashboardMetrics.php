<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardMetrics
{
    /**
     * @return array<string, mixed>
     */
    public function today(): array
    {
        $orders = Order::whereDate('placed_at', today());

        return [
            'orders' => (clone $orders)->count(),
            'revenue' => (float) (clone $orders)->sum('grand_total'),
            'tax' => (float) (clone $orders)->sum('tax_total'),
            'units' => (int) OrderItem::whereIn('order_id', (clone $orders)->select('id'))->sum('quantity'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function allTime(): array
    {
        return [
            'orders' => Order::count(),
            'voided' => Order::onlyTrashed()->count(),
            'revenue' => (float) Order::sum('grand_total'),
            'customers' => Customer::count(),
        ];
    }

    /**
     * @return Collection<int, array{date: Carbon, revenue: float, orders: int}>
     */
    public function revenueTrend(int $days = 14): Collection
    {
        $rows = Order::query()
            ->where('placed_at', '>=', today()->subDays($days - 1))
            ->selectRaw('DATE(placed_at) as day, SUM(grand_total) as revenue, COUNT(*) as orders')
            ->groupBy('day')
            ->pluck('revenue', 'day');

        $counts = Order::query()
            ->where('placed_at', '>=', today()->subDays($days - 1))
            ->selectRaw('DATE(placed_at) as day, COUNT(*) as orders')
            ->groupBy('day')
            ->pluck('orders', 'day');

        return collect(range($days - 1, 0))->map(function (int $ago) use ($rows, $counts): array {
            $date = today()->subDays($ago);
            $key = $date->toDateString();

            return [
                'date' => $date,
                'revenue' => (float) ($rows[$key] ?? 0),
                'orders' => (int) ($counts[$key] ?? 0),
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
            ->orderByDesc('units_sold')
            ->limit($limit)
            ->get()
            ->filter(fn (Product $product): bool => (int) $product->units_sold > 0)
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
}
