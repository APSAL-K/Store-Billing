<?php

namespace App\Http\Controllers;

use App\Services\DashboardMetrics;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(DashboardMetrics $metrics): View
    {
        return view('dashboard.index', [
            'today' => $metrics->today(),
            'allTime' => $metrics->allTime(),
            'trend' => $metrics->revenueTrend(),
            'bestSellers' => $metrics->bestSellers(),
            'recentOrders' => $metrics->recentOrders(),
            'lowStock' => $metrics->lowStock(),
        ]);
    }
}
