<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Wallet\Presentation\Http\Controllers\WalletController;

/*
|--------------------------------------------------------------------------
| Wallet Module API Routes
|--------------------------------------------------------------------------
|
| Here is where you register API routes for the Wallet module. These
| routes are loaded by the WalletServiceProvider within a group which
| is assigned the "api" middleware group. 
|
| Rate limiting and auth are enforced aggressively since these handle money.
| All operations mapped here directly proxy to strictly isolated UseCases.
| Fraud ML checks and Idempotency deduplication occurs at the internal boundary.
|
| This file configures:
| 1. Authentication (Sanctum)
| 2. Rate limiting (Wallet specific parameters to prevent brute force attacks)
| 3. Explicit HTTP Verbs matching exact operations without overloading paths
|
| Strict 9-layer Hexagonal boundaries demand that this file only references
| Controllers and does NOT hold inline closures or domain logic snippets.
*/

Route::prefix('api/v1/wallet')->middleware(['api', 'auth:sanctum'])->group(function () {
    
    /**
     * Credit Wallet Endpoint
     * 
     * Handles inbound requests to add money to an existing wallet. 
     * This mapping handles explicit verification before pushing processing to the Application.
     * Protected explicitly by a 30-request-per-minute throttle per user.
     */
    Route::post('/credit', [WalletController::class, 'credit'])
        ->name('wallet.credit')
        ->middleware('throttle:30,1'); 

    /**
     * Debit Wallet Endpoint
     * 
     * Handles inbound requests to strictly deduct money from an existing wallet.
     * Prevents negative boundaries inherently by executing pre-flight UseCase configurations.
     * Protected explicitly by a 30-request-per-minute throttle to prevent concurrent deduction spam.
     */
    Route::post('/debit', [WalletController::class, 'debit'])
        ->name('wallet.debit')
        ->middleware('throttle:30,1'); 

    /**
     * Transfer Inter-Wallet Endpoint
     * 
     * Coordinates shifting primitives explicitly from a source boundary to a target boundary.
     * Enforces rigid atomic transactional locks resolving cleanly during dual state updates.
     * Rate limits are tighter here since dual row-locks apply pressure to the database.
     */
    Route::post('/transfer', [WalletController::class, 'transfer'])
        ->name('wallet.transfer')
        ->middleware('throttle:15,1'); 
});

/*
|--------------------------------------------------------------------------
| Internal Service Endpoints (Optional Expansion)
|--------------------------------------------------------------------------
|
| If microservices or decoupled modular monolith bounded contexts needed 
| to trigger internal operations without HTTP Auth via JWT, they would be 
| registered here under a separate secure middleware chain.
|
| Current architecture resolves these inter-domain dependencies via events
| or Application Layer Application-Services directly depending on the integration 
| style defined in the global strict constraints. No generic proxy stubs allowed.
*/
