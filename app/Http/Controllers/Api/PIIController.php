<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Warehouse\Domain\Services\RightToBeForgottenService;
use Modules\Warehouse\Domain\Services\ConsentManagementService;
use Modules\Warehouse\Domain\Exceptions\ConsentException;

/**
 * PII Controller
 * 
 * API endpoints for PII management per 152-ФЗ compliance
 * 
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class PIIController extends Controller
{
    public function __construct(
        private readonly RightToBeForgottenService $rightToBeForgotten,
        private readonly ConsentManagementService $consentService
    ) {}

    /**
     * Request PII deletion (right to be forgotten)
     */
    public function requestDeletion(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'reason' => 'required|string|max:1000',
            'tables' => 'array',
            'tables.*' => 'string|in:inventory_counts,stock_movements,controlled_substances_log,documents',
        ]);

        $userId = (int) $validated['user_id'];
        $reason = $validated['reason'];
        $tables = $validated['tables'] ?? null;
        $approverId = auth()->id();

        try {
            if ($tables) {
                $results = $this->rightToBeForgotten->partialPIIDeletion($userId, $tables);
            } else {
                $results = $this->rightToBeForgotten->processDeletionRequest($userId, $reason, $approverId);
            }

            return response()->json([
                'success' => true,
                'message' => 'PII deletion request processed',
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process deletion request',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check PII existence
     */
    public function checkPIIExistence(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $userId = (int) $validated['user_id'];

        try {
            $locations = $this->rightToBeForgotten->checkPIIExistence($userId);

            return response()->json([
                'success' => true,
                'user_id' => $userId,
                'has_pii' => !empty($locations),
                'locations' => $locations,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check PII existence',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate deletion report
     */
    public function generateDeletionReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $userId = (int) $validated['user_id'];

        try {
            $report = $this->rightToBeForgotten->generateDeletionReport($userId);

            return response()->json([
                'success' => true,
                'report' => $report,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate deletion report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create consent
     */
    public function createConsent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'consent_type' => 'required|string|in:personal_data_processing,data_sharing,marketing_communications,analytics,biometric_data',
            'consent_text' => 'required|string',
        ]);

        $userId = (int) $validated['user_id'];
        $consentType = $validated['consent_type'];
        $consentText = $validated['consent_text'];
        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();

        try {
            $consentId = $this->consentService->createConsent(
                $userId,
                $consentType,
                $consentText,
                $ipAddress,
                $userAgent
            );

            return response()->json([
                'success' => true,
                'message' => 'Consent created successfully',
                'consent_id' => $consentId,
            ]);
        } catch (ConsentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create consent',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Revoke consent
     */
    public function revokeConsent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'consent_id' => 'required|string|exists:pii_consents,id',
            'reason' => 'required|string|max:1000',
        ]);

        $consentId = $validated['consent_id'];
        $reason = $validated['reason'];
        $revokedBy = auth()->id();

        try {
            $this->consentService->revokeConsent($consentId, $reason, $revokedBy);

            return response()->json([
                'success' => true,
                'message' => 'Consent revoked successfully',
            ]);
        } catch (ConsentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to revoke consent',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check active consents
     */
    public function checkConsents(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'consent_type' => 'nullable|string|in:personal_data_processing,data_sharing,marketing_communications,analytics,biometric_data',
        ]);

        $userId = (int) $validated['user_id'];
        $consentType = $validated['consent_type'] ?? null;

        try {
            if ($consentType) {
                $hasConsent = $this->consentService->hasActiveConsent($userId, $consentType);
                return response()->json([
                    'success' => true,
                    'user_id' => $userId,
                    'consent_type' => $consentType,
                    'has_active_consent' => $hasConsent,
                ]);
            }

            $consents = $this->consentService->getActiveConsents($userId);
            return response()->json([
                'success' => true,
                'user_id' => $userId,
                'active_consents' => $consents,
            ]);
        } catch (ConsentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check consents',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get consent history
     */
    public function getConsentHistory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $userId = (int) $validated['user_id'];

        try {
            $history = $this->consentService->getConsentHistory($userId);

            return response()->json([
                'success' => true,
                'user_id' => $userId,
                'history' => $history,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get consent history',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check required consents
     */
    public function checkRequiredConsents(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'required_consents' => 'required|array',
            'required_consents.*' => 'string|in:personal_data_processing,data_sharing,marketing_communications,analytics,biometric_data',
        ]);

        $userId = (int) $validated['user_id'];
        $requiredConsents = $validated['required_consents'];

        try {
            $missing = $this->consentService->checkRequiredConsents($userId, $requiredConsents);

            return response()->json([
                'success' => true,
                'user_id' => $userId,
                'has_all_required' => empty($missing),
                'missing_consents' => $missing,
            ]);
        } catch (ConsentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check required consents',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
