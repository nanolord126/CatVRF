<?php declare(strict_types=1);

namespace App\Domains\Pet\Http\Controllers;

use App\Domains\Pet\Models\PetReview;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class PetReviewController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        try {
            $correlationId = Str::uuid()->toString();

            $review = PetReview::create([
                ...$request->validated(),
                'tenant_id' => tenant()->id,
                'reviewer_id' => auth()->id(),
                'correlation_id' => $correlationId,
                'uuid' => Str::uuid(),
                'status' => 'pending',
            ]);

            return response()->json([
                'success' => true,
                'data' => $review,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Throwable $e) {
            \Log::error('Failed to create review', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create review',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        try {
            $review = PetReview::findOrFail($id);
            $this->authorize('update', $review);
            $correlationId = Str::uuid()->toString();

            $review->update([
                ...$request->validated(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $review,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update review',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        try {
            $review = PetReview::findOrFail($id);
            $this->authorize('delete', $review);
            $correlationId = Str::uuid()->toString();

            $review->delete();

            return response()->json([
                'success' => true,
                'message' => 'Review deleted',
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete review',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function approve($id): JsonResponse
    {
        try {
            $this->authorize('approve', PetReview::class);
            $review = PetReview::findOrFail($id);
            $correlationId = Str::uuid()->toString();

            $review->update([
                'status' => 'approved',
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $review,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve review',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function reject($id): JsonResponse
    {
        try {
            $this->authorize('approve', PetReview::class);
            $review = PetReview::findOrFail($id);
            $correlationId = Str::uuid()->toString();

            $review->update([
                'status' => 'rejected',
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $review,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject review',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function getClinicReviews($clinicId): JsonResponse
    {
        try {
            $reviews = PetReview::where('clinic_id', $clinicId)
                ->where('status', 'approved')
                ->with(['reviewer', 'vet'])
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $reviews,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve reviews',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function getVetReviews($vetId): JsonResponse
    {
        try {
            $reviews = PetReview::where('vet_id', $vetId)
                ->where('status', 'approved')
                ->with(['reviewer', 'appointment'])
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $reviews,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve reviews',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function myReviews(): JsonResponse
    {
        try {
            $reviews = PetReview::where('reviewer_id', auth()->id())
                ->where('tenant_id', tenant()->id)
                ->with(['clinic', 'vet'])
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $reviews,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve reviews',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }
}
