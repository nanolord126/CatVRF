<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Domains\HomeServices\Http\Controllers\ContractorController;
use App\Domains\HomeServices\Http\Controllers\ServiceListingController;
use App\Domains\HomeServices\Http\Controllers\ServiceJobController;
use App\Domains\HomeServices\Http\Controllers\ServiceReviewController;
use App\Domains\HomeServices\Http\Controllers\ServiceCategoryController;

Route::prefix('home-services')->group(function () {
    
    // Public endpoints
    Route::get('/categories', [ServiceCategoryController::class, 'index']);
    Route::get('/contractors', [ContractorController::class, 'index']);
    Route::get('/contractors/{id}', [ContractorController::class, 'show']);
    Route::get('/contractors/{id}/listings', [ServiceListingController::class, 'byContractor']);
    Route::get('/listings', [ServiceListingController::class, 'index']);
    Route::get('/listings/{id}', [ServiceListingController::class, 'show']);
    Route::get('/listings/{id}/reviews', [ServiceReviewController::class, 'byListing']);
    Route::get('/contractors/{id}/reviews', [ServiceReviewController::class, 'byContractor']);

    // Authenticated endpoints
    Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
        
        // Job Management
        Route::post('/jobs', [ServiceJobController::class, 'create']);
        Route::get('/jobs/my', [ServiceJobController::class, 'myJobs']);
        Route::get('/jobs/{id}', [ServiceJobController::class, 'show']);
        Route::patch('/jobs/{id}/accept', [ServiceJobController::class, 'accept']);
        Route::patch('/jobs/{id}/start', [ServiceJobController::class, 'start']);
        Route::patch('/jobs/{id}/complete', [ServiceJobController::class, 'complete']);
        Route::patch('/jobs/{id}/cancel', [ServiceJobController::class, 'cancel']);

        // Reviews
        Route::post('/reviews', [ServiceReviewController::class, 'store']);
        Route::get('/reviews/my', [ServiceReviewController::class, 'myReviews']);
        Route::patch('/reviews/{id}', [ServiceReviewController::class, 'update']);
        Route::delete('/reviews/{id}', [ServiceReviewController::class, 'delete']);

        // Contractor endpoints
        Route::post('/contractors/register', [ContractorController::class, 'register']);
        Route::get('/contractor/profile', [ContractorController::class, 'myProfile']);
        Route::patch('/contractor/profile', [ContractorController::class, 'updateProfile']);
        Route::get('/contractor/earnings', [ContractorController::class, 'myEarnings']);
        Route::get('/contractor/schedule', [ContractorController::class, 'getSchedule']);
        Route::patch('/contractor/schedule', [ContractorController::class, 'updateSchedule']);

        // Listings management
        Route::post('/listings', [ServiceListingController::class, 'store']);
        Route::patch('/listings/{id}', [ServiceListingController::class, 'update']);
        Route::delete('/listings/{id}', [ServiceListingController::class, 'delete']);

        // Disputes
        Route::post('/jobs/{jobId}/dispute', [ServiceJobController::class, 'createDispute']);
        Route::get('/disputes/my', [ServiceJobController::class, 'myDisputes']);
    });

    // Admin endpoints
    Route::middleware(['auth:sanctum', 'tenant', 'admin'])->group(function () {
        Route::patch('/jobs/{id}/resolve', [ServiceJobController::class, 'resolve']);
        Route::patch('/disputes/{id}/resolve', [ServiceJobController::class, 'resolveDispute']);
        Route::delete('/contractors/{id}', [ContractorController::class, 'delete']);
        Route::delete('/listings/{id}', [ServiceListingController::class, 'forceDelete']);
    });
});
