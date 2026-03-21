<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['api'])->prefix('api/realestate')->group(function () {
    // Public endpoints
    Route::get('/properties', [\App\Domains\RealEstate\Http\Controllers\PropertyController::class, 'index']);
    Route::get('/properties/{property}', [\App\Domains\RealEstate\Http\Controllers\PropertyController::class, 'show']);
    Route::get('/properties/{property}/details', [\App\Domains\RealEstate\Http\Controllers\PropertyController::class, 'details']);
    Route::get('/rentals', [\App\Domains\RealEstate\Http\Controllers\RentalListingController::class, 'index']);
    Route::get('/sales', [\App\Domains\RealEstate\Http\Controllers\SaleListingController::class, 'index']);

    // Authenticated endpoints
    Route::middleware(['auth:api'])->group(function () {
        Route::post('/viewings', [\App\Domains\RealEstate\Http\Controllers\ViewingAppointmentController::class, 'create']);
        Route::get('/viewings', [\App\Domains\RealEstate\Http\Controllers\ViewingAppointmentController::class, 'index']);
        Route::patch('/viewings/{appointment}', [\App\Domains\RealEstate\Http\Controllers\ViewingAppointmentController::class, 'update']);
        Route::delete('/viewings/{appointment}', [\App\Domains\RealEstate\Http\Controllers\ViewingAppointmentController::class, 'cancel']);

        Route::post('/mortgages', [\App\Domains\RealEstate\Http\Controllers\MortgageController::class, 'store']);
        Route::get('/mortgages', [\App\Domains\RealEstate\Http\Controllers\MortgageController::class, 'index']);
        Route::get('/mortgages/{application}', [\App\Domains\RealEstate\Http\Controllers\MortgageController::class, 'show']);
        Route::get('/mortgages/{application}/calculate', [\App\Domains\RealEstate\Http\Controllers\MortgageController::class, 'calculate']);

        // Owner endpoints
        Route::post('/properties', [\App\Domains\RealEstate\Http\Controllers\PropertyController::class, 'store']);
        Route::patch('/properties/{property}', [\App\Domains\RealEstate\Http\Controllers\PropertyController::class, 'update']);
        Route::post('/properties/{property}/list-rental', [\App\Domains\RealEstate\Http\Controllers\RentalListingController::class, 'store']);
        Route::post('/properties/{property}/list-sale', [\App\Domains\RealEstate\Http\Controllers\SaleListingController::class, 'store']);
    });

    // Admin endpoints
    Route::middleware(['auth:api', 'admin'])->group(function () {
        Route::delete('/properties/{property}', [\App\Domains\RealEstate\Http\Controllers\PropertyController::class, 'destroy']);
        Route::delete('/rentals/{listing}', [\App\Domains\RealEstate\Http\Controllers\RentalListingController::class, 'destroy']);
        Route::delete('/sales/{listing}', [\App\Domains\RealEstate\Http\Controllers\SaleListingController::class, 'destroy']);
    });
});
