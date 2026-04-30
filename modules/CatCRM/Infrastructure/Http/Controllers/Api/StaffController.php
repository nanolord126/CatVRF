<?php

declare(strict_types=1);

namespace Modules\CatCRM\Infrastructure\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\CatCRM\Application\Services\Staff\CRMStaffIntegrationService;
use Modules\CatCRM\Application\DTOs\Staff\CreateEmployeeDTO;
use Modules\CatCRM\Application\DTOs\Staff\UpdateEmployeeDTO;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * StaffController — Layer 2: API/HTTP Layer
 * 
 * API endpoints for Staff HRM
 * Part of 9-layer architecture
 */
final class StaffController extends Controller
{
    public function __construct(
        private CRMStaffIntegrationService $staffService,
    ) {}

    /**
     * Create employee
     */
    public function store(Request $request): JsonResponse
    {
        $dto = CreateEmployeeDTO::fromArray($request->validated());
        $result = $this->staffService->createEmployee($dto, $request->user()?->id);

        return response()->json($result, 201);
    }

    /**
     * Get employee profile
     */
    public function show(Request $request, int $employeeId): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $profile = $this->staffService->getEmployeeProfile($tenantId, $employeeId, $request->user()?->id);

        return response()->json($profile);
    }

    /**
     * Update employee
     */
    public function update(Request $request, int $employeeId): JsonResponse
    {
        $dto = UpdateEmployeeDTO::fromArray($request->validated());
        $tenantId = $request->user()->tenant_id;
        $result = $this->staffService->updateEmployee($employeeId, $dto, $tenantId, $request->user()?->id);

        return response()->json(['success' => $result]);
    }

    /**
     * Analyze performance
     */
    public function analyzePerformance(Request $request, int $employeeId): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $result = $this->staffService->analyzePerformance($tenantId, $employeeId, $request->user()?->id);

        return response()->json($result);
    }

    /**
     * Predict burnout risk
     */
    public function predictBurnout(Request $request, int $employeeId): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $result = $this->staffService->predictBurnoutRisk($tenantId, $employeeId, $request->user()?->id);

        return response()->json($result);
    }

    /**
     * Get leaderboard
     */
    public function leaderboard(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $period = $request->get('period', 'weekly');
        $result = $this->staffService->getLeaderboard($tenantId, $period, $request->user()?->id);

        return response()->json($result);
    }

    /**
     * Get employee shifts
     */
    public function shifts(Request $request, int $employeeId): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        // TODO: Implement shifts method in CRMStaffIntegrationService
        return response()->json([]);
    }

    /**
     * Create shift
     */
    public function createShift(Request $request, int $employeeId): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $shiftData = $request->validated();
        $result = $this->staffService->createShift($tenantId, $shiftData, $request->user()?->id);

        return response()->json($result, 201);
    }

    /**
     * Record wellness metrics
     */
    public function recordWellness(Request $request, int $employeeId): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $metrics = $request->validated();
        $result = $this->staffService->recordWellnessMetrics($tenantId, $employeeId, $metrics, $request->user()?->id);

        return response()->json($result, 201);
    }

    /**
     * Award badge
     */
    public function awardBadge(Request $request, int $employeeId): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $badgeId = $request->get('badge_id');
        $result = $this->staffService->awardBadge($tenantId, $employeeId, $badgeId, $request->user()?->id);

        return response()->json($result, 201);
    }

    /**
     * Submit peer review
     */
    public function submitPeerReview(Request $request, int $employeeId): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $reviewerId = $request->user()->id;
        $reviewData = $request->validated();
        $result = $this->staffService->submitPeerReview($tenantId, $reviewerId, $employeeId, $reviewData, $request->user()?->id);

        return response()->json($result, 201);
    }

    /**
     * Request leave
     */
    public function requestLeave(Request $request, int $employeeId): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $leaveData = $request->validated();
        $result = $this->staffService->requestLeave($tenantId, $employeeId, $leaveData, $request->user()?->id);

        return response()->json($result, 201);
    }

    /**
     * Approve leave
     */
    public function approveLeave(Request $request, int $leaveId): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $approverId = $request->user()->id;
        $result = $this->staffService->approveLeave($tenantId, $leaveId, $approverId, $request->user()?->id);

        return response()->json($result);
    }

    /**
     * Get manager dashboard
     */
    public function managerDashboard(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $managerId = $request->user()->id;
        $filters = $request->all();
        $result = $this->staffService->getManagerDashboard($tenantId, $managerId, $filters, $request->user()?->id);

        return response()->json($result);
    }

    /**
     * Onboard new employee
     */
    public function onboard(Request $request, int $employeeId): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $onboardingData = $request->validated();
        $result = $this->staffService->onboardNewEmployee($tenantId, $employeeId, $onboardingData, $request->user()?->id);

        return response()->json($result, 201);
    }
}
