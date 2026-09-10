<?php

use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('products', [ProductController::class, 'index'])->name('api.products.index');
Route::post('products', [ProductController::class, 'store'])->name('api.products.store');
Route::put('products/{product}', [ProductController::class, 'update'])->name('api.products.update');
Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('api.products.destroy');
Route::get('products/low-stock', [ProductController::class, 'lowStock'])->name('api.products.low-stock');

Route::get('customers', [CustomerController::class, 'index'])->name('api.customers.index');
Route::get('customers/lookup', [CustomerController::class, 'show'])->name('api.customers.lookup');
Route::post('customers', [CustomerController::class, 'store'])->name('api.customers.store');
Route::put('customers/{customer}', [CustomerController::class, 'update'])->name('api.customers.update');
Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])->name('api.customers.destroy');

Route::get('orders', [OrderController::class, 'index'])->name('api.orders.index');
Route::post('orders', [OrderController::class, 'store'])->name('api.orders.store');
Route::put('orders/{order}', [OrderController::class, 'update'])->withTrashed()->name('api.orders.update');
Route::delete('orders/{order}', [OrderController::class, 'destroy'])->withTrashed()->name('api.orders.destroy');

Route::post('products/{product}/restock', [ProductController::class, 'restock'])->name('api.products.restock');
