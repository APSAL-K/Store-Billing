<?php

namespace App\Services;

use App\Data\NewOrderData;
use App\Models\Product;
use App\Support\Money;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class OrderTotals
{
    /**
     * @param  EloquentCollection<int, Product>  $products
     */
    public function for(NewOrderData $data, EloquentCollection $products): PricedOrder
    {
        $items = [];
        $subtotalMinor = 0;
        $taxTotalMinor = 0;

        foreach ($data->lines as $line) {
            $product = $products[$line->productId];

            $lineSubtotal = Money::toMinor($product->unit_price) * $line->quantity;
            $lineTax = Money::percentageOf($lineSubtotal, $product->tax_percentage);

            $items[] = [
                'product_id' => $product->id,
                'quantity' => $line->quantity,
                'unit_price' => Money::toDecimal(Money::toMinor($product->unit_price)),
                'tax_percentage' => $product->tax_percentage,
                'line_subtotal' => Money::toDecimal($lineSubtotal),
                'line_tax' => Money::toDecimal($lineTax),
                'line_total' => Money::toDecimal($lineSubtotal + $lineTax),
            ];

            $subtotalMinor += $lineSubtotal;
            $taxTotalMinor += $lineTax;
        }

        return new PricedOrder(
            items: $items,
            subtotalMinor: $subtotalMinor,
            taxTotalMinor: $taxTotalMinor,
            grandTotalMinor: $subtotalMinor + $taxTotalMinor,
        );
    }
}
