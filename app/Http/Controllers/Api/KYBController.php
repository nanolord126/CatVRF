<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Carbon\CarbonImmutable;

use App\Http\Controllers\Controller;
use App\Services\KYB\KYBService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\KYBVerification;

final class KYBController extends Controller
{
    public function __construct(
        private readonly KYBService $kybService,
    ) {}

    public function startVerification(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|string',
            'business_group_id' => 'required|integer',
            'inn' => 'required|string|size:10',
            'correlation_id' => 'nullable|string',
        ]);

        $result = $this->kybService->startVerification(
            $validated['tenant_id'],
            $validated['business_group_id'],
            $validated['inn'],
            $validated['correlation_id'] ?? '',
        );

        return new JsonResponse([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function getStatus(string $tenantId): JsonResponse
    {
        $status = $this->kybService->getVerificationStatus($tenantId);

        return new JsonResponse([
            'success' => true,
            'data' => $status,
        ]);
    }

    public function reScreen(string $tenantId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'correlation_id' => 'nullable|string',
        ]);

        $result = $this->kybService->reScreenBusiness(
            $tenantId,
            $validated['correlation_id'] ?? '',
        );

        return new JsonResponse([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function approve(int $id, Request $request): JsonResponse
    {
        // Check if critical changes are blocked due to VPN/Proxy detection
        if ($this->isCriticalChangesBlocked()) {
            return $this->getCriticalChangesBlockedResponse();
        }

        $kybVerification = KYBVerification::findOrFail($id);

        $validated = $request->validate([
            'notes' => ['nullable', 'string'],
        ]);

        $kybVerification->update([
            'verification_status' => 'approved',
            'manual_review_required' => false,
            'manual_reviewed_at' => CarbonImmutable::now(),
            'manual_review_notes' => $validated['notes'] ?? null,
        ]);

        return new JsonResponse([
            'success' => true,
            'message' => 'KYB verification approved',
        ]);
    }

    public function reject(int $id, Request $request): JsonResponse
    {
        $kybVerification = KYBVerification::findOrFail($id);

        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        $kybVerification->update([
            'verification_status' => 'rejected',
            'manual_review_required' => false,
            'manual_reviewed_at' => CarbonImmutable::now(),
            'manual_review_notes' => $validated['reason'],
        ]);

        return new JsonResponse([
            'success' => true,
            'message' => 'KYB verification rejected',
        ]);
    }

    public function pendingReview(): JsonResponse
    {
        $pending = KYBVerification::with(['tenant', 'businessGroup'])
            ->pendingReview()
            ->orderByDesc('created_at')
            ->get();

        return new JsonResponse([
            'success' => true,
            'data' => $pending,
        ]);
    }
}
