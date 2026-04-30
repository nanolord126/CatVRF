<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Fitness\Presentation\Http\Controllers\FitnessController;

Route::middleware(['api', 'auth:sanctum'])->group(function () {
    Route::prefix('fitness')->group(function () {
        Route::prefix('memberships')->group(function () {
            Route::post('/', [FitnessController::class, 'createMembership'])
                ->name('fitness.memberships.create');

            Route::post('{membershipId}/freeze', [FitnessController::class, 'freezeMembership'])
                ->name('fitness.memberships.freeze');

            Route::post('{membershipId}/unfreeze', [FitnessController::class, 'unfreezeMembership'])
                ->name('fitness.memberships.unfreeze');

            Route::post('{membershipId}/cancel', [FitnessController::class, 'cancelMembership'])
                ->name('fitness.memberships.cancel');

            Route::post('{membershipId}/visit', [FitnessController::class, 'useVisit'])
                ->name('fitness.memberships.use-visit');

            Route::post('{membershipId}/extend', [FitnessController::class, 'extendMembership'])
                ->name('fitness.memberships.extend');
        });

        Route::get('clients/{clientId}/memberships', [FitnessController::class, 'getClientMemberships'])
            ->name('fitness.clients.memberships');

        Route::get('clients/{clientId}/stats', [FitnessController::class, 'getMembershipStats'])
            ->name('fitness.clients.stats');
    });
});
