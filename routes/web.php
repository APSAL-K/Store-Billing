<?php

use App\Http\Controllers\BillingController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('dashboard');

Route::get('pos', [BillingController::class, 'create'])->name('billing.index');
Route::get('orders/{order}/edit', [BillingController::class, 'edit'])->name('billing.edit');

Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
Route::get('orders/{order}', [OrderController::class, 'show'])->withTrashed()->name('orders.show');

Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');

Route::get('products', [ProductController::class, 'index'])->name('products.index');
Route::get('products/{product}', [ProductController::class, 'show'])->name('products.show');
