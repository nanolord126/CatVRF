<?php declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Onboarding\DaDataService;
use App\Services\Onboarding\AIIdentityService;
use App\Services\Onboarding\DocumentVerificationService;
use App\Services\Onboarding\BusinessRegistrationService;
use App\Services\Onboarding\OnboardingService;

final class OnboardingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // DaData Service
        $this->app->singleton(DaDataService::class, function () {
            return new DaDataService(
                apiKey: config('onboarding.dadata.api_key'),
                secretKey: config('onboarding.dadata.secret_key'),
            );
        });

        // AI Identity Service
        $this->app->singleton(AIIdentityService::class, function () {
            return new AIIdentityService(
                provider: config('onboarding.ai_identity.provider'),
                apiKey: config('onboarding.ai_identity.api_key'),
                apiEndpoint: config('onboarding.ai_identity.api_endpoint'),
            );
        });

        // Document Verification Service
        $this->app->singleton(DocumentVerificationService::class, function () {
            return new DocumentVerificationService(
                provider: config('onboarding.document_verification.provider'),
                apiKey: config('onboarding.document_verification.api_key'),
                apiEndpoint: config('onboarding.document_verification.api_endpoint'),
            );
        });

        // Business Registration Service
        $this->app->singleton(BusinessRegistrationService::class, function ($app) {
            return new BusinessRegistrationService(
                daDataService: $app->make(DaDataService::class),
                aiIdentityService: $app->make(AIIdentityService::class),
                documentService: $app->make(DocumentVerificationService::class),
                fraudControl: $app->make(\App\Services\FraudControlService::class),
            );
        });

        // Onboarding Service (unified orchestrator)
        $this->app->singleton(OnboardingService::class, function ($app) {
            return new OnboardingService(
                businessRegistration: $app->make(BusinessRegistrationService::class),
                daDataService: $app->make(DaDataService::class),
                aiIdentityService: $app->make(AIIdentityService::class),
                documentService: $app->make(DocumentVerificationService::class),
                fraudControl: $app->make(\App\Services\FraudControlService::class),
            );
        });
    }

    public function boot(): void
    {
        // Load routes
        $this->loadRoutesFrom(base_path('routes/api/onboarding.api.php'));
    }
}
