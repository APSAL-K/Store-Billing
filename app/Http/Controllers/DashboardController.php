<?php

namespace App\Http\Controllers;

use App\Services\DashboardMetrics;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(DashboardMetrics $metrics): View
    {
        return view('dashboard.index', [
            'headline' => $metrics->headline(),
            'allTime' => $metrics->allTime(),
            'inventory' => $metrics->inventoryValue(),
            'trend' => $metrics->revenueTrend(),
            'byHour' => $metrics->tradeByHour(),
            'bestSellers' => $metrics->bestSellers(),
            'topCustomers' => $metrics->topCustomers(),
            'recentOrders' => $metrics->recentOrders(),
            'lowStock' => $metrics->lowStock(),
            'movements' => $metrics->recentMovements(),
        ]);
    }
}
