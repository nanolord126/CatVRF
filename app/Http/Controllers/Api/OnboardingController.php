<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Onboarding\OnboardingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Auth\AuthManager;

/**
 * Onboarding API Controller for business and user registration.
 * Production-ready with fraud control and audit logging.
 */
final class OnboardingController extends Controller
{
    public function __construct(
        private readonly AuthManager $auth,
        private readonly OnboardingService $onboardingService,
    ) {}

    /**
     * Validate INN (step 1 of business registration)
     */
    public function validateInn(Request $request): JsonResponse
    {
        $request->validate([
            'inn' => 'required|string|size:10|size:12',
        ]);

        $result = $this->onboardingService->validateInn(
            inn: $request->inn,
            correlationId: $request->header('X-Correlation-ID') ?? '',
        );

        return new JsonResponse($result);
    }

    /**
     * Suggest companies by name (autocomplete)
     */
    public function suggestCompanies(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|min:3',
            'count' => 'nullable|integer|min:1|max:10',
        ]);

        $results = $this->onboardingService->suggestCompanies(
            name: $request->name,
            count: $request->input('count', 5),
        );

        return new JsonResponse([
            'success' => true,
            'companies' => $results,
        ]);
    }

    /**
     * Upload documents (step 2 of business registration)
     */
    public function uploadDocuments(Request $request): JsonResponse
    {
        $request->validate([
            'egrul' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB
            'passport' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'expected_inn' => 'required|string',
        ]);

        $documents = [];
        if ($request->hasFile('egrul')) {
            $documents['egrul'] = $request->file('egrul');
        }
        if ($request->hasFile('passport')) {
            $documents['passport'] = $request->file('passport');
        }

        $result = $this->onboardingService->uploadDocuments(
            userId: $this->auth->id(),
            documents: $documents,
            expectedInn: $request->expected_inn,
            correlationId: $request->header('X-Correlation-ID') ?? '',
        );

        return new JsonResponse($result);
    }

    /**
     * Verify identity with FIO + photo (step 3 for all users)
     */
    public function verifyIdentity(Request $request): JsonResponse
    {
        $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'middle_name' => 'nullable|string',
            'photo' => 'required|file|image|max:5120', // 5MB
            'passport_photo' => 'nullable|file|image|max:5120',
        ]);

        $result = $this->onboardingService->verifyIdentity(
            userId: $this->auth->id(),
            firstName: $request->first_name,
            lastName: $request->last_name,
            middleName: $request->middle_name,
            photo: $request->file('photo'),
            passportPhoto: $request->file('passport_photo'),
            correlationId: $request->header('X-Correlation-ID') ?? '',
        );

        return new JsonResponse($result);
    }

    /**
     * Register full business (complete flow)
     */
    public function registerBusiness(Request $request): JsonResponse
    {
        $request->validate([
            'inn' => 'required|string|size:10|size:12',
            'documents' => 'required|array',
            'documents.egrul' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'documents.passport' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'selfie_photo' => 'nullable|file|image|max:5120',
        ]);

        $documents = [];
        foreach ($request->file('documents') as $type => $file) {
            $documents[$type] = $file;
        }

        $selfiePath = null;
        if ($request->hasFile('selfie_photo')) {
            $selfiePath = $request->file('selfie_photo')->store('selfies', 'secure');
        }

        $result = $this->onboardingService->registerBusiness(
            ownerUserId: $this->auth->id(),
            inn: $request->inn,
            documents: $documents,
            selfiePhotoPath: $selfiePath,
            correlationId: $request->header('X-Correlation-ID') ?? '',
        );

        return new JsonResponse($result, 201);
    }

    /**
     * Register branch (simplified flow for tenant-owners)
     */
    public function registerBranch(Request $request): JsonResponse
    {
        $request->validate([
            'parent_tenant_id' => 'required|string|exists:tenants,id',
            'branch_inn' => 'required|string|size:10|size:12',
            'branch_name' => 'required|string|max:255',
            'branch_address' => 'required|string|max:500',
        ]);

        $result = $this->onboardingService->registerBranch(
            ownerUserId: $this->auth->id(),
            parentTenantId: $request->parent_tenant_id,
            branchInn: $request->branch_inn,
            branchName: $request->branch_name,
            branchAddress: $request->branch_address,
            correlationId: $request->header('X-Correlation-ID') ?? '',
        );

        return new JsonResponse($result, 201);
    }

    /**
     * Give consent for data processing (152-ФZ compliance)
     */
    public function giveConsent(Request $request): JsonResponse
    {
        $result = $this->onboardingService->giveConsent($this->auth->id());

        return new JsonResponse($result);
    }

    /**
     * Get onboarding status for current user
     */
    public function getOnboardingStatus(): JsonResponse
    {
        $status = $this->onboardingService->getOnboardingStatus($this->auth->id());

        return new JsonResponse($status);
    }

    /**
     * Get tenant verification status
     */
    public function getTenantVerificationStatus(Request $request): JsonResponse
    {
        $request->validate([
            'tenant_id' => 'required|string|exists:tenants,id',
        ]);

        $status = $this->onboardingService->getTenantVerificationStatus($request->tenant_id);

        return new JsonResponse($status);
    }
}
