<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Browsable order history. The email filter mirrors the API endpoint; with
     * no filter it shows the whole day's trade.
     */
    public function index(Request $request): View
    {
        $email = $request->string('email')->lower()->trim()->value();

        $orders = Order::query()
            ->with(['customer', 'items'])
            ->withSum('items', 'quantity')
            ->when($email !== '', fn ($query) => $query->whereHas(
                'customer',
                fn ($q) => $q->where('email', 'like', '%'.$email.'%')
            ))
            ->latest('placed_at')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return view('orders.index', [
            'orders' => $orders,
            'email' => $email,
        ]);
    }

    public function show(Order $order): View
    {
        return view('orders.show', [
            'order' => $order->load(['customer', 'items.product']),
        ]);
    }
}
