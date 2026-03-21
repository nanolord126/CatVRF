<?php declare(strict_types=1);

namespace App\Domains\Sports\Http\Controllers;

use App\Domains\Sports\Models\Studio;
use App\Domains\Sports\Models\Membership;
use App\Domains\Sports\Models\Purchase;
use App\Domains\Sports\Services\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

final class StudioController
{
    public function __construct(private PurchaseService $purchaseService) {}

    public function index(): JsonResponse
    {
        try {
            $studios = Studio::where('is_verified', true)
                ->with(['trainers', 'classes', 'reviews'])
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $studios,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Failed to list studios', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to list studios'], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $studio = Studio::with(['trainers', 'classes', 'memberships', 'reviews'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $studio,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Studio not found'], 404);
        }
    }

    public function store(): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        try {
            $this->authorize('create', Studio::class);

            $validated = request()->validate([
                'name' => 'required|string|max:255',
                'description' => 'required|string',
                'address' => 'required|string',
                'phone' => 'nullable|string',
                'website' => 'nullable|url',
                'amenities' => 'nullable|array',
            ]);

            $correlationId = Str::uuid();

            $studio = Studio::create([
                'tenant_id' => tenant('id'),
                'owner_id' => auth()->id(),
                'name' => $validated['name'],
                'description' => $validated['description'],
                'address' => $validated['address'],
                'phone' => $validated['phone'] ?? null,
                'website' => $validated['website'] ?? null,
                'amenities' => $validated['amenities'] ?? [],
                'correlation_id' => $correlationId,
            ]);

            \Log::channel('audit')->info('Studio created', ['studio_id' => $studio->id, 'correlation_id' => $correlationId]);

            return response()->json(['success' => true, 'data' => $studio, 'correlation_id' => $correlationId], 201);
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Failed to create studio', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to create studio'], 500);
        }
    }

    public function update(int $id): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        try {
            $studio = Studio::findOrFail($id);
            $this->authorize('update', $studio);

            $validated = request()->validate(['name' => 'sometimes|string|max:255', 'description' => 'sometimes|string']);

            $correlationId = Str::uuid();
            $studio->update($validated + ['correlation_id' => $correlationId]);

            return response()->json(['success' => true, 'data' => $studio, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update studio'], 500);
        }
    }

    public function delete(int $id): JsonResponse
    {
        try {
            $studio = Studio::findOrFail($id);
            $this->authorize('delete', $studio);
            $correlationId = Str::uuid();
            $studio->delete();

            return response()->json(['success' => true, 'message' => 'Studio deleted', 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete studio'], 500);
        }
    }

    public function createMembership(int $studioId): JsonResponse
    {
        try {
            $studio = Studio::findOrFail($studioId);
            $this->authorize('update', $studio);

            $validated = request()->validate([
                'name' => 'required|string|max:255',
                'type' => 'required|in:monthly,quarterly,yearly',
                'price' => 'required|numeric|min:0',
                'classes_per_month' => 'nullable|integer',
            ]);

            $membership = Membership::create([
                'tenant_id' => tenant('id'),
                'studio_id' => $studioId,
                'name' => $validated['name'],
                'type' => $validated['type'],
                'duration_days' => match($validated['type']) {
                    'monthly' => 30,
                    'quarterly' => 90,
                    'yearly' => 365,
                },
                'price' => $validated['price'],
                'classes_per_month' => $validated['classes_per_month'],
                'is_active' => true,
            ]);

            return response()->json(['success' => true, 'data' => $membership], 201);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to create membership'], 500);
        }
    }

    public function updateMembership(int $id): JsonResponse
    {
        try {
            $membership = Membership::findOrFail($id);
            $studio = $membership->studio;
            $this->authorize('update', $studio);

            $validated = request()->validate(['name' => 'sometimes|string', 'price' => 'sometimes|numeric', 'is_active' => 'sometimes|boolean']);
            $membership->update($validated);

            return response()->json(['success' => true, 'data' => $membership]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update membership'], 500);
        }
    }

    public function deleteMembership(int $id): JsonResponse
    {
        try {
            $membership = Membership::findOrFail($id);
            $studio = $membership->studio;
            $this->authorize('update', $studio);
            $membership->delete();

            return response()->json(['success' => true, 'message' => 'Membership deleted']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete membership'], 500);
        }
    }

    public function purchaseMembership(int $membershipId): JsonResponse
    {
        try {
            $membership = Membership::findOrFail($membershipId);
            $correlationId = Str::uuid();

            $purchase = DB::transaction(function () use ($membership, $correlationId) {
                return $this->purchaseService->createPurchase(
                    $membership->studio_id,
                    auth()->id(),
                    $membership->id,
                    'membership',
                    $membership->name,
                    1,
                    $membership->price,
                    $correlationId
                );
            });

            return response()->json(['success' => true, 'data' => $purchase, 'correlation_id' => $correlationId], 201);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Purchase failed'], 500);
        }
    }

    public function myPurchases(): JsonResponse
    {
        try {
            $purchases = Purchase::where('buyer_id', auth()->id())->paginate(20);
            return response()->json(['success' => true, 'data' => $purchases, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to list purchases'], 500);
        }
    }

    public function refundPurchase(int $id): JsonResponse
    {
        try {
            $purchase = Purchase::findOrFail($id);
            $this->authorize('refund', $purchase);
            $correlationId = Str::uuid();

            DB::transaction(fn() => $this->purchaseService->refundPurchase($purchase, 'User requested refund', $correlationId));

            return response()->json(['success' => true, 'message' => 'Refund processed', 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Refund failed'], 500);
        }
    }

    public function analytics(int $id): JsonResponse
    {
        try {
            $studio = Studio::findOrFail($id);
            $this->authorize('update', $studio);

            $analytics = [
                'total_bookings' => $studio->bookings()->count(),
                'total_revenue' => $studio->purchases()->sum('total_amount'),
                'total_commission' => $studio->purchases()->sum('commission_amount'),
                'members_count' => $studio->purchases()->distinct('buyer_id')->count(),
                'average_rating' => $studio->rating,
                'total_reviews' => $studio->reviews()->count(),
            ];

            return response()->json(['success' => true, 'data' => $analytics, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch analytics'], 500);
        }
    }
}
