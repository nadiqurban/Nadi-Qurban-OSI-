<?php

use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Middleware\LogApiRequest;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public integration API — /api/v1 (docs/api.md)
|--------------------------------------------------------------------------
| Bearer tokens issued in Tetapan › Integrasi API (Sanctum), 120 req/min per key.
| Abilities: "read" for GET endpoints, "write" to create orders.
*/

Route::prefix('v1')->middleware(['auth:sanctum', LogApiRequest::class, 'throttle:api-v1'])->name('api.')->group(function () {
    Route::get('/products', [ProductController::class, 'index'])->middleware('abilities:read')->name('products.index');
    Route::get('/orders/{reference}', [OrderController::class, 'show'])->middleware('abilities:read')->name('orders.show');
    Route::get('/orders/{reference}/status', [OrderController::class, 'status'])->middleware('abilities:read')->name('orders.status');
    Route::post('/orders', [OrderController::class, 'store'])->middleware('abilities:write')->name('orders.store');
});
