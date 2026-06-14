<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CarbonListingController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => ['status' => 'ok']);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Carbon listings — seller side (static /mine route must come before {param})
    Route::get('/carbon-listings/mine', [CarbonListingController::class, 'mine']);
    Route::post('/carbon-listings', [CarbonListingController::class, 'store']);
    Route::get('/carbon-listings/{carbonListing}', [CarbonListingController::class, 'show']);
    Route::post('/carbon-listings/{carbonListing}/recall', [CarbonListingController::class, 'recall']);
});
