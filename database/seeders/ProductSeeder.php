<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    private const CATALOGUE = [
        ['code' => 'GRO-1001', 'name' => 'Amul Milk 1L', 'unit_price' => 68.00, 'tax_percentage' => 0, 'stock_on_hand' => 9, 'low_stock_threshold' => 12],
        ['code' => 'GRO-1002', 'name' => 'Brown Bread 400g', 'unit_price' => 45.00, 'tax_percentage' => 0, 'stock_on_hand' => 4, 'low_stock_threshold' => null],
        ['code' => 'GRO-1003', 'name' => 'Farm Eggs (12)', 'unit_price' => 84.00, 'tax_percentage' => 0, 'stock_on_hand' => 2, 'low_stock_threshold' => null],
        ['code' => 'GRO-1004', 'name' => 'Basmati Rice 5kg', 'unit_price' => 640.00, 'tax_percentage' => 5, 'stock_on_hand' => 46, 'low_stock_threshold' => null],
        ['code' => 'GRO-1005', 'name' => 'Toor Dal 1kg', 'unit_price' => 172.00, 'tax_percentage' => 5, 'stock_on_hand' => 88, 'low_stock_threshold' => null],
        ['code' => 'GRO-1006', 'name' => 'Sunflower Oil 1L', 'unit_price' => 148.50, 'tax_percentage' => 5, 'stock_on_hand' => 61, 'low_stock_threshold' => null],
        ['code' => 'SNK-2001', 'name' => 'Parle-G Biscuit 250g', 'unit_price' => 30.00, 'tax_percentage' => 18, 'stock_on_hand' => 240, 'low_stock_threshold' => 40],
        ['code' => 'SNK-2002', 'name' => 'Lay\'s Classic 52g', 'unit_price' => 20.00, 'tax_percentage' => 12, 'stock_on_hand' => 175, 'low_stock_threshold' => 40],
        ['code' => 'SNK-2003', 'name' => 'Bourbon Biscuit 150g', 'unit_price' => 45.00, 'tax_percentage' => 18, 'stock_on_hand' => 7, 'low_stock_threshold' => 25],
        ['code' => 'PER-3001', 'name' => 'Colgate Strong Teeth 200g', 'unit_price' => 115.00, 'tax_percentage' => 18, 'stock_on_hand' => 54, 'low_stock_threshold' => null],
        ['code' => 'PER-3002', 'name' => 'Dove Soap 100g', 'unit_price' => 62.00, 'tax_percentage' => 18, 'stock_on_hand' => 130, 'low_stock_threshold' => null],
        ['code' => 'PER-3003', 'name' => 'Head & Shoulders 340ml', 'unit_price' => 385.00, 'tax_percentage' => 18, 'stock_on_hand' => 23, 'low_stock_threshold' => null],
        ['code' => 'HOM-4001', 'name' => 'Surf Excel 1kg', 'unit_price' => 210.00, 'tax_percentage' => 18, 'stock_on_hand' => 38, 'low_stock_threshold' => null],
        ['code' => 'HOM-4002', 'name' => 'Vim Dishwash Bar', 'unit_price' => 25.00, 'tax_percentage' => 18, 'stock_on_hand' => 0, 'low_stock_threshold' => null],
        ['code' => 'BEV-5001', 'name' => 'Tata Tea Gold 500g', 'unit_price' => 320.00, 'tax_percentage' => 5, 'stock_on_hand' => 42, 'low_stock_threshold' => null],
    ];

    public function run(): void
    {
        foreach (self::CATALOGUE as $attributes) {
            $product = Product::updateOrCreate(['code' => $attributes['code']], $attributes);

            StockMovement::create([
                'product_id' => $product->id,
                'reason' => StockMovement::REASON_OPENING_BALANCE,
                'quantity_change' => $product->stock_on_hand,
                'balance_after' => $product->stock_on_hand,
            ]);
        }
    }
}
