<?php

use App\Http\Controllers\Admin\CarbonListingReviewController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CarbonListingController;
use App\Http\Controllers\PurchaseController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => ['status' => 'ok']);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    // Session
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Carbon listings — read (static routes first)
    Route::get('/carbon-listings/mine', [CarbonListingController::class, 'mine']);
    Route::get('/carbon-listings', [CarbonListingController::class, 'index']);
    Route::get('/carbon-listings/{carbonListing}', [CarbonListingController::class, 'show']);

    // Carbon listings — write
    Route::post('/carbon-listings', [CarbonListingController::class, 'store']);
    Route::post('/carbon-listings/{carbonListing}/recall', [CarbonListingController::class, 'recall']);
    Route::post('/carbon-listings/{carbonListing}/purchase', [CarbonListingController::class, 'purchase']);

    // Purchases (buyer history)
    Route::get('/purchases', [PurchaseController::class, 'index']);

    // Admin review
    Route::prefix('admin/carbon-listings')->group(function () {
        Route::get('/pending', [CarbonListingReviewController::class, 'pending']);
        Route::post('/{carbonListing}/approve', [CarbonListingReviewController::class, 'approve']);
        Route::post('/{carbonListing}/reject', [CarbonListingReviewController::class, 'reject']);
    });
});
