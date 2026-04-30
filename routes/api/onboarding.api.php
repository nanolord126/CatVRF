<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OnboardingController;

/*
|--------------------------------------------------------------------------
| Onboarding API Routes
|--------------------------------------------------------------------------
|
| Business and user onboarding endpoints with verification flows.
| All routes require authentication.
|
*/

Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    // INN validation (step 1)
    Route::post('/onboarding/validate-inn', [OnboardingController::class, 'validateInn']);

    // Company suggestions (autocomplete)
    Route::get('/onboarding/suggest-companies', [OnboardingController::class, 'suggestCompanies']);

    // Document upload (step 2)
    Route::post('/onboarding/upload-documents', [OnboardingController::class, 'uploadDocuments']);

    // Identity verification (step 3 - for all users)
    Route::post('/onboarding/verify-identity', [OnboardingController::class, 'verifyIdentity']);

    // Full business registration (complete flow)
    Route::post('/onboarding/register-business', [OnboardingController::class, 'registerBusiness']);

    // Branch registration (simplified flow)
    Route::post('/onboarding/register-branch', [OnboardingController::class, 'registerBranch']);

    // Consent for data processing (152-ФZ compliance)
    Route::post('/onboarding/give-consent', [OnboardingController::class, 'giveConsent']);

    // Get onboarding status
    Route::get('/onboarding/status', [OnboardingController::class, 'getOnboardingStatus']);

    // Get tenant verification status
    Route::get('/onboarding/tenant-status/{tenant_id}', [OnboardingController::class, 'getTenantVerificationStatus']);
});
