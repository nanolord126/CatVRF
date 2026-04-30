<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\Compliance\PersonalDataComplianceService;
use App\Services\Compliance\ComplianceRequirementService;
use App\Models\ComplianceIntegration;
use App\Models\WarehouseLicense;
use Modules\Payment\Application\Services\AMLService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * General Compliance API Controller.
 *
 * Provides REST API endpoints for broader compliance features:
 * - 152-ФЗ: Personal data compliance
 * - Warehouse licenses management
 * - Chestnyznak integration
 * - EGISZ integration
 * - OneC sync logs
 */
final class ComplianceController extends Controller
{
    public function __construct(
        private readonly PersonalDataComplianceService $personalDataCompliance,
        private readonly ComplianceRequirementService $requirementService,
        private readonly AMLService $amlService,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | 152-ФЗ Personal Data Compliance Endpoints
    |--------------------------------------------------------------------------
    */

    /**
     * Run full compliance check for 152-ФЗ.
     */
    public function checkCompliance(): JsonResponse
    {
        $results = $this->personalDataCompliance->checkCompliance();

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    /**
     * Generate audit report for Roskomnadzor.
     */
    public function generateAuditReport(): JsonResponse
    {
        $report = $this->personalDataCompliance->generateAuditReport();

        return response()->json([
            'success' => true,
            'data' => [
                'report' => $report,
                'generated_at' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Get audit checklist for Roskomnadzor.
     */
    public function getAuditChecklist(): JsonResponse
    {
        $checklist = $this->personalDataCompliance->getAuditChecklist();

        return response()->json([
            'success' => true,
            'data' => $checklist,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Warehouse Licenses Endpoints
    |--------------------------------------------------------------------------
    */

    /**
     * Get warehouse licenses for tenant.
     */
    public function getWarehouseLicenses(Request $request): JsonResponse
    {
        $tenantId = $request->query('tenant_id', Auth::check() ? Auth::user()->tenant_id : null);
        $licenseType = $request->query('license_type');
        $isActive = $request->query('is_active');

        $query = WarehouseLicense::query();

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($licenseType) {
            $query->where('license_type', $licenseType);
        }

        if ($isActive !== null) {
            $query->where('is_active', filter_var($isActive, FILTER_VALIDATE_BOOLEAN));
        }

        $licenses = $query->with('warehouse')->orderBy('expiry_date', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $licenses->map(fn($license) => [
                'id' => $license->id,
                'warehouse_id' => $license->warehouse_id,
                'warehouse_name' => $license->warehouse?->name,
                'license_type' => $license->license_type,
                'license_number' => $license->license_number,
                'issued_date' => $license->issued_date->format('Y-m-d'),
                'expiry_date' => $license->expiry_date->format('Y-m-d'),
                'issued_by' => $license->issued_by,
                'is_active' => $license->is_active,
                'days_until_expiry' => now()->diffInDays($license->expiry_date, false),
                'is_expiring_soon' => now()->diffInDays($license->expiry_date, false) <= 30,
                'is_expired' => $license->expiry_date->isPast(),
            ]),
        ]);
    }

    /**
     * Create new warehouse license.
     */
    public function createWarehouseLicense(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'license_type' => 'required|string|in:pharmacy,medicine_storage,narcotic,psychotropic,poisonous',
            'license_number' => 'required|string|max:255',
            'issued_date' => 'required|date',
            'expiry_date' => 'required|date|after:issued_date',
            'issued_by' => 'required|string|max:255',
        ]);

        $license = WarehouseLicense::create([
            ...$validated,
            'tenant_id' => Auth::user()->tenant_id,
            'created_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $license,
        ], 201);
    }

    /**
     * Revoke warehouse license.
     */
    public function revokeWarehouseLicense(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'revocation_reason' => 'required|string',
        ]);

        $license = WarehouseLicense::findOrFail($id);

        $license->update([
            'is_active' => false,
            'revoked_at' => now(),
            'revoked_by' => Auth::id(),
            'revocation_reason' => $validated['revocation_reason'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $license,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Compliance Integrations Endpoints
    |--------------------------------------------------------------------------
    */

    /**
     * Get compliance integrations for tenant.
     */
    public function getIntegrations(Request $request): JsonResponse
    {
        $tenantId = $request->query('tenant_id', Auth::check() ? Auth::user()->tenant_id : null);
        $type = $request->query('type');

        $query = ComplianceIntegration::query();

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($type) {
            $query->where('type', $type);
        }

        $integrations = $query->orderBy('last_checked_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $integrations->map(fn($integration) => [
                'id' => $integration->id,
                'uuid' => $integration->uuid,
                'type' => $integration->type,
                'inn' => $integration->inn,
                'status' => $integration->status,
                'last_checked_at' => $integration->last_checked_at?->toISOString(),
                'error_message' => $integration->error_message,
            ]),
        ]);
    }

    /**
     * Get integration status for specific type.
     */
    public function getIntegrationStatus(Request $request, string $type): JsonResponse
    {
        $tenantId = Auth::user()->tenant_id;
        $hasIntegration = $this->requirementService->hasActiveIntegration($tenantId, $type);

        $integration = ComplianceIntegration::where('tenant_id', $tenantId)
            ->where('type', $type)
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'type' => $type,
                'has_active_integration' => $hasIntegration,
                'integration' => $integration ? [
                    'id' => $integration->id,
                    'uuid' => $integration->uuid,
                    'inn' => $integration->inn,
                    'status' => $integration->status,
                    'last_checked_at' => $integration->last_checked_at?->toISOString(),
                ] : null,
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Dashboard Statistics
    |--------------------------------------------------------------------------
    */

    /**
     * Get overall compliance dashboard statistics.
     */
    public function getDashboardStats(Request $request): JsonResponse
    {
        $tenantId = Auth::user()->tenant_id;

        $stats = [
            'personal_data' => [
                'compliance_score' => $this->personalDataCompliance->checkCompliance()['score'] ?? 0,
                'overall_status' => $this->personalDataCompliance->checkCompliance()['overall_status'] ?? 'unknown',
            ],
            'warehouse_licenses' => [
                'total' => WarehouseLicense::where('tenant_id', $tenantId)->count(),
                'active' => WarehouseLicense::where('tenant_id', $tenantId)->where('is_active', true)->count(),
                'expiring_soon' => WarehouseLicense::where('tenant_id', $tenantId)
                    ->where('is_active', true)
                    ->whereBetween('expiry_date', [now(), now()->addDays(30)])
                    ->count(),
                'expired' => WarehouseLicense::where('tenant_id', $tenantId)
                    ->where('expiry_date', '<', now())
                    ->count(),
            ],
            'integrations' => [
                'total' => ComplianceIntegration::where('tenant_id', $tenantId)->count(),
                'connected' => ComplianceIntegration::where('tenant_id', $tenantId)
                    ->where('status', 'connected')
                    ->count(),
                'disconnected' => ComplianceIntegration::where('tenant_id', $tenantId)
                    ->where('status', '!=', 'connected')
                    ->count(),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | PII Deletion Requests
    |--------------------------------------------------------------------------
    */

    /**
     * Get PII deletion requests.
     */
    public function getPiiDeletionRequests(Request $request): JsonResponse
    {
        $tenantId = Auth::user()->tenant_id;
        $status = $request->query('status');

        $query = DB::table('pii_deletion_requests');

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $requests = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | PII Consents
    |--------------------------------------------------------------------------
    */

    /**
     * Get PII consents.
     */
    public function getPiiConsents(Request $request): JsonResponse
    {
        $tenantId = Auth::user()->tenant_id;
        $userId = $request->query('user_id');
        $consentType = $request->query('consent_type');

        $query = DB::table('pii_consents');

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($consentType) {
            $query->where('consent_type', $consentType);
        }

        $consents = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $consents,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | AML Integration with Domain Services
    |--------------------------------------------------------------------------
    */

    /**
     * Get AML check by UUID using domain service.
     * TODO: AmlCheck model not yet implemented - comment out until model is created
     */
    /*
    public function getDomainAMLCheck(string $uuid): JsonResponse
    {
        $check = AmlCheck::where('uuid', $uuid)->first();

        if (!$check) {
            return response()->json([
                'success' => false,
                'error' => 'AML check not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'uuid' => $check->uuid,
                'user_id' => $check->user_id,
                'risk_score' => $check->risk_score / 100,
                'risk_level' => $this->getRiskLevel($check->risk_score),
                'checks_performed' => $check->risk_factors ?? [],
                'flags' => $check->risk_factors ?? [],
                'status' => $check->passed ? 'approved' : 'rejected',
                'checked_at' => $check->checked_at->toISOString(),
                'reported_to_rosfinmonitoring' => $check->suspiciousOperation?->reporting_status === 'reported',
            ],
        ]);
    }
    */

    /**
     * Get AML checks for user using domain service.
     * TODO: AmlCheck model not yet implemented - comment out until model is created
     */
    /*
    public function getDomainUserAMLChecks(Request $request, int $userId): JsonResponse
    {
        $validated = $request->validate([
            'limit' => 'nullable|integer|min:1|max:100',
            'offset' => 'nullable|integer|min:0',
        ]);

        $limit = $validated['limit'] ?? 50;
        $offset = $validated['offset'] ?? 0;

        $checks = AmlCheck::where('user_id', $userId)
            ->where('tenant_id', Auth::user()->tenant_id)
            ->orderBy('checked_at', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $checks->map(fn($check) => [
                'uuid' => $check->uuid,
                'user_id' => $check->user_id,
                'risk_score' => $check->risk_score / 100,
                'risk_level' => $this->getRiskLevel($check->risk_score),
                'checks_performed' => $check->risk_factors ?? [],
                'flags' => $check->risk_factors ?? [],
                'status' => $check->passed ? 'approved' : 'rejected',
                'checked_at' => $check->checked_at->toISOString(),
                'reported_to_rosfinmonitoring' => $check->suspiciousOperation?->reporting_status === 'reported',
            ]),
        ]);
    }
    */

    /**
     * Get pending AML checks requiring manual review.
     * TODO: AmlCheck model not yet implemented - comment out until model is created
     */
    /*
    public function getDomainPendingAMLChecks(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $limit = $validated['limit'] ?? 100;

        $checks = AmlCheck::failed()
            ->where('tenant_id', Auth::user()->tenant_id)
            ->where('risk_score', '>=', 70)
            ->orderBy('checked_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $checks->map(fn($check) => [
                'uuid' => $check->uuid,
                'user_id' => $check->user_id,
                'risk_score' => $check->risk_score / 100,
                'risk_level' => $this->getRiskLevel($check->risk_score),
                'checks_performed' => $check->risk_factors ?? [],
                'flags' => $check->risk_factors ?? [],
                'status' => $check->passed ? 'approved' : 'manual_review',
                'checked_at' => $check->checked_at->toISOString(),
                'reported_to_rosfinmonitoring' => $check->suspiciousOperation?->reporting_status === 'reported',
            ]),
        ]);
    }
    */

    /**
     * Get AML statistics using domain service.
     * TODO: AMLService method not yet implemented - comment out until method is created
     */
    /*
    public function getDomainAMLStats(Request $request): JsonResponse
    {
        $tenantId = Auth::user()->tenant_id;
        $stats = $this->amlService->getTenantAMLStats($tenantId);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
    */

    /**
     * Get high-risk users using domain service.
     * TODO: AMLService method not yet implemented - comment out until method is created
     */
    /*
    public function getHighRiskAMLUsers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'threshold' => 'nullable|integer|min:0|max:100',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $threshold = $validated['threshold'] ?? 70;
        $limit = $validated['limit'] ?? 100;

        $users = $this->amlService->getHighRiskUsers($threshold, $limit);

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }
    */

    /**
     * Get recent suspicious operations using domain service.
     * TODO: AMLService method not yet implemented - comment out until method is created
     */
    /*
    public function getSuspiciousOperations(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $limit = $validated['limit'] ?? 100;

        $operations = $this->amlService->getRecentSuspiciousOperations($limit);

        return response()->json([
            'success' => true,
            'data' => $operations,
        ]);
    }
    */

    /**
     * Helper method to determine risk level from score.
     */
    private function getRiskLevel(int $score): string
    {
        if ($score >= 85) {
            return 'critical';
        }
        if ($score >= 70) {
            return 'high';
        }
        if ($score >= 40) {
            return 'medium';
        }
        return 'low';
    }
}
