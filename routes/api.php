<?php

use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('products', [ProductController::class, 'index'])->name('api.products.index');
Route::get('products/low-stock', [ProductController::class, 'lowStock'])->name('api.products.low-stock');

Route::get('orders', [OrderController::class, 'index'])->name('api.orders.index');
Route::post('orders', [OrderController::class, 'store'])->name('api.orders.store');
