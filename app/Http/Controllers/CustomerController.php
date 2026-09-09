<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->value();

        $customers = Customer::query()
            ->when($search !== '', fn ($query) => $query->where(
                fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')
            ))
            ->withCount('orders')
            ->withSum('orders as lifetime_value', 'grand_total')
            ->withMax('orders as last_order_at', 'placed_at')
            ->orderByDesc('lifetime_value')
            ->paginate(15)
            ->withQueryString();

        return view('customers.index', [
            'customers' => $customers,
            'search' => $search,
        ]);
    }

    public function show(Customer $customer): View
    {
        return view('customers.show', [
            'customer' => $customer->loadCount('orders'),
            'orders' => $customer->orders()
                ->withTrashed()
                ->withSum('items', 'quantity')
                ->latest('placed_at')
                ->latest('id')
                ->paginate(15),
            'lifetimeValue' => (float) $customer->orders()->sum('grand_total'),
        ]);
    }
}
