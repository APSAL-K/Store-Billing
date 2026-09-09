<x-mail::message>
# Thanks for shopping with us

Hi {{ $order->customer->name }}, here is the bill for order **{{ $order->reference }}**, placed on
{{ $order->placed_at->format('d M Y \a\t g:i A') }}.

<x-mail::table>
| Item | Qty | Rate | Total |
| :--- | :-: | ---: | ----: |
@foreach ($order->items as $item)
| {{ $item->product->name }} | {{ $item->quantity }} | ₹{{ number_format((float) $item->unit_price, 2) }} | ₹{{ number_format((float) $item->line_total, 2) }} |
@endforeach
</x-mail::table>

**Subtotal:** ₹{{ number_format((float) $order->subtotal, 2) }}
**Tax:** ₹{{ number_format((float) $order->tax_total, 2) }}
**Grand total:** ₹{{ number_format((float) $order->grand_total, 2) }}

@if ($order->amount_tendered !== null)
You paid ₹{{ number_format((float) $order->amount_tendered, 2) }} and received
₹{{ number_format((float) $order->change_due, 2) }} in change.
@endif

<x-mail::button :url="route('orders.show', $order)">
View this bill
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
