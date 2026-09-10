<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Support\PerPage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $email = $request->string('email')->lower()->trim()->value();

        $orders = Order::query()
            ->when($request->boolean('deleted'), fn ($query) => $query->onlyTrashed())
            ->with(['customer', 'items'])
            ->withSum('items', 'quantity')
            ->when($email !== '', fn ($query) => $query->whereHas(
                'customer',
                fn ($q) => $q->where('email', 'like', '%'.$email.'%')
            ))
            ->latest('placed_at')
            ->latest('id')
            ->paginate(PerPage::from($request))
            ->withQueryString();

        return view('orders.index', [
            'orders' => $orders,
            'email' => $email,
            'showingDeleted' => $request->boolean('deleted'),
            'deletedCount' => Order::onlyTrashed()->count(),
        ]);
    }

    public function show(Order $order): View
    {
        return view('orders.show', [
            'order' => $order->load(['customer', 'items.product']),
        ]);
    }
}
