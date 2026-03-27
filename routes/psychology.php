<?php

declare(strict_types=1);

use App\Domains\Medical\Psychology\Http\Controllers\PsychologicalApiController;
use App\Livewire\Psychology\PsychologicalShowcase;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Psychology Domain Routes
|--------------------------------------------------------------------------
*/

// API Layer
Route::prefix('api/v1/psychology')
    ->middleware(['api', 'auth:sanctum', 'tenant'])
    ->group(function () {
        Route::get('/therapists', [PsychologicalApiController::class, 'index']);
        Route::get('/therapists/{psychologist}', [PsychologicalApiController::class, 'show']);
        Route::post('/ai-match', [PsychologicalApiController::class, 'aiMatch']);
        Route::post('/bookings', [PsychologicalApiController::class, 'storeBooking']);
    });

// Frontend Layer
Route::middleware(['web', 'auth', 'tenant'])
    ->group(function () {
        Route::get('/marketplace/psychology', PsychologicalShowcase::class)
            ->name('marketplace.psychology');
    });
