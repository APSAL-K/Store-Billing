<?php

use App\Http\Controllers\BillingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [BillingController::class, 'index'])->name('billing.index');
Route::get('/orders/{order}', [BillingController::class, 'show'])->name('billing.receipt');
