<?php

declare(strict_types=1);

namespace App\Domains\Sports\Http\Controllers;

use App\Domains\Sports\Services\SportsDynamicPricingService;
use App\Domains\Sports\Models\Membership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

final class DynamicPricingController extends Controller
{
    public function __construct(
        private SportsDynamicPricingService $service,
    ) {}

    public function calculatePrice(Request $request, int $venueId): JsonResponse
    {
        $validated = $request->validate([
            'service_type' => 'required|string|in:single_visit,monthly_membership,personal_training,group_class',
            'is_b2b' => 'sometimes|boolean',
        ]);

        $result = $this->service->calculateDynamicPrice(
            venueId: $venueId,
            serviceType: $validated['service_type'],
            isB2B: $validated['is_b2b'] ?? false,
            correlationId: $request->header('X-Correlation-ID', Str::uuid()->toString()),
        );

        return response()->json($result);
    }

    public function createFlashMembership(Request $request, int $venueId): JsonResponse
    {
        $validated = $request->validate([
            'membership_type' => 'required|string|in:monthly,quarterly,annual',
            'duration_days' => 'required|integer|min:1|max:365',
            'base_price' => 'required|numeric|min:0',
            'amount' => 'required|numeric|min:0',
            'business_group_id' => 'nullable|integer|exists:business_groups,id',
        ]);

        $tenantId = function_exists('tenant') && tenant() ? tenant()->id : 0;
        
        $membershipData = array_merge($validated, [
            'tenant_id' => $tenantId,
            'business_group_id' => $validated['business_group_id'] ?? null,
        ]);

        $membership = $this->service->createFlashMembership(
            venueId: $venueId,
            userId: auth()->id(),
            membershipData: $membershipData,
            correlationId: $request->header('X-Correlation-ID', Str::uuid()->toString()),
        );

        return response()->json([
            'membership' => $membership->toArray(),
        ]);
    }

    public function getBulkPricing(Request $request, int $venueId): JsonResponse
    {
        $validated = $request->validate([
            'employee_count' => 'required|integer|min:1',
        ]);

        $result = $this->service->getBulkMembershipPricing(
            venueId: $venueId,
            employeeCount: $validated['employee_count'],
            correlationId: $request->header('X-Correlation-ID', Str::uuid()->toString()),
        );

        return response()->json($result);
    }

    public function updatePricing(Request $request, int $venueId): JsonResponse
    {
        $this->authorize('updatePricing', $venueId);

        $this->service->updatePricingBasedOnLoad(
            venueId: $venueId,
            correlationId: $request->header('X-Correlation-ID', Str::uuid()->toString()),
        );

        return response()->json([
            'success' => true,
            'message' => 'Pricing updated based on current load',
        ]);
    }

    public function showMembership(int $membershipId): JsonResponse
    {
        $membership = Membership::with(['gym', 'user'])->findOrFail($membershipId);

        $this->authorize('view', $membership);

        return response()->json([
            'membership' => $membership->toArray(),
        ]);
    }
}
