<?php

use App\Http\Controllers\Admin\CarbonListingReviewController;
use App\Http\Controllers\Admin\WorkerApplicationReviewController;
use App\Http\Controllers\Admin\WorkerJobReportReviewController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CarbonListingController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\WorkerApplicationController;
use App\Http\Controllers\WorkerJobController;
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

    // Admin review — carbon listings
    Route::prefix('admin/carbon-listings')->group(function () {
        Route::get('/pending', [CarbonListingReviewController::class, 'pending']);
        Route::post('/{carbonListing}/approve', [CarbonListingReviewController::class, 'approve']);
        Route::post('/{carbonListing}/reject', [CarbonListingReviewController::class, 'reject']);
    });

    // Worker applications
    Route::get('/worker-applications/mine', [WorkerApplicationController::class, 'mine']);
    Route::post('/worker-applications', [WorkerApplicationController::class, 'store']);

    // Admin review — worker applications
    Route::prefix('admin/worker-applications')->group(function () {
        Route::get('/pending', [WorkerApplicationReviewController::class, 'pending']);
        Route::post('/{workerApplication}/approve', [WorkerApplicationReviewController::class, 'approve']);
        Route::post('/{workerApplication}/reject', [WorkerApplicationReviewController::class, 'reject']);
    });

    // Worker jobs — read (static routes first)
    Route::get('/worker-jobs/open', [WorkerJobController::class, 'openQueue']);
    Route::get('/worker-jobs/mine', [WorkerJobController::class, 'mine']);
    Route::get('/worker-jobs/{workerJob}', [WorkerJobController::class, 'show']);

    // Worker jobs — write
    Route::post('/worker-jobs/{workerJob}/claim', [WorkerJobController::class, 'claim']);
    Route::post('/worker-jobs/{workerJob}/report', [WorkerJobController::class, 'submitReport']);

    // Admin review — job reports
    Route::prefix('admin/job-reports')->group(function () {
        Route::get('/pending', [WorkerJobReportReviewController::class, 'pending']);
        Route::post('/{workerJobReport}/approve', [WorkerJobReportReviewController::class, 'approve']);
        Route::post('/{workerJobReport}/reject', [WorkerJobReportReviewController::class, 'reject']);
    });
});
