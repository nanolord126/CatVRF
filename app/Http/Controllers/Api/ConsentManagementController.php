<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Privacy\ConsentManagementService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

final readonly class ConsentManagementController extends Controller
{
    public function __construct(
        private readonly ConsentManagementService $consentManagement,
    ) {}

    /**
     * Grant consent for specific purposes
     */
    public function grant(Request $request): JsonResponse
    {
        $request->validate([
            'consent_purposes' => 'required|array',
            'consent_purposes.*' => 'string|in:biometric,behavioral,location,medical,payment,analytics,marketing,sharing,ai_training',
        ]);

        $result = $this->consentManagement->grantConsent(
            userId: (int) auth()->id(),
            consentPurposes: $request->input('consent_purposes'),
            correlationId: $request->header('X-Correlation-ID') ?? '',
        );

        return new JsonResponse($result);
    }

    /**
     * Revoke consent for specific purposes
     */
    public function revoke(Request $request): JsonResponse
    {
        $request->validate([
            'consent_purposes' => 'required|array',
            'consent_purposes.*' => 'string|in:biometric,behavioral,location,medical,payment,analytics,marketing,sharing,ai_training',
        ]);

        $result = $this->consentManagement->revokeConsent(
            userId: (int) auth()->id(),
            consentPurposes: $request->input('consent_purposes'),
            correlationId: $request->header('X-Correlation-ID') ?? '',
        );

        return new JsonResponse($result);
    }

    /**
     * Get user consents
     */
    public function index(Request $request): JsonResponse
    {
        $consents = $this->consentManagement->getUserConsents(
            userId: (int) auth()->id(),
        );

        return new JsonResponse($consents);
    }

    /**
     * Check if user has specific consent
     */
    public function check(Request $request, string $consentType): JsonResponse
    {
        $hasConsent = $this->consentManagement->hasConsent(
            userId: (int) auth()->id(),
            consentType: $consentType,
        );

        return new JsonResponse([
            'has_consent' => $hasConsent,
            'consent_type' => $consentType,
        ]);
    }
}
