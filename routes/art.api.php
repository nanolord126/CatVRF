<?php
declare(strict_types=1);

use App\Domains\Art\Http\Controllers\ArtController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'auth:sanctum', 'tenant', 'throttle:60,1'])
    ->prefix('art')
    ->group(function (): void {
        Route::post('projects', [ArtController::class, 'storeProject'])->name('art.projects.store');
        Route::post('projects/{project}/artworks', [ArtController::class, 'storeArtwork'])->name('art.artworks.store');
        Route::post('projects/{project}/reviews', [ArtController::class, 'storeReview'])->name('art.reviews.store');
    });
