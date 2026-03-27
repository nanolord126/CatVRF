<?php declare(strict_types=1);

namespace App\Domains\Medical\Http\Controllers;

use App\Domains\Medical\Models\MedicalReview;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

final class MedicalReviewController
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function doctorReviews(int $doctorId): JsonResponse
    {
        try {
            $reviews = MedicalReview::where('doctor_id', $doctorId)
                ->where('status', 'approved')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $reviews,
                'correlation_id' => request()->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Failed to fetch reviews'], 500);
        }
    }

    public function store(Request $request, int $doctorId): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

        try {
            $validated = $request->all();
            $review = DB::transaction(function () use ($validated, $doctorId) {
                return MedicalReview::create([
                    'tenant_id' => auth()->user()->tenant_id,
                    'doctor_id' => $doctorId,
                    'reviewer_id' => auth()->user()->id,
                    'appointment_id' => ($validated['appointment_id'] ?? null),
                    'rating' => ($validated['rating'] ?? null),
                    'comment' => ($validated['comment'] ?? null),
                    'review_aspects' => ($validated['review_aspects'] ?? null),
                    'verified_appointment' => !!($validated['appointment_id'] ?? null),
                    'status' => 'pending',
                    'correlation_id' => $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
                ]);
            });

            Log::channel('audit')->info('Review created', ['review_id' => $review->id]);

            return response()->json([
                'success' => true,
                'data' => $review,
                'correlation_id' => $request->header('X-Correlation-ID'),
            ], 201);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Failed to create review'], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

        try {
            $review = MedicalReview::findOrFail($id);
            $this->authorize('update', $review);

            $review->update([
                'comment' => $request->input('comment', $review->comment),
                'rating' => $request->input('rating', $review->rating),
                'correlation_id' => $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
            ]);

            Log::channel('audit')->info('Review updated', ['review_id' => $review->id]);

            return response()->json(['success' => true, 'data' => $review]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Update failed'], 500);
        }
    }

    public function delete(int $id): JsonResponse
    {
        try {
            $review = MedicalReview::findOrFail($id);
            $this->authorize('delete', $review);

            $review->delete();

            Log::channel('audit')->info('Review deleted', ['review_id' => $review->id]);

            return response()->json(['success' => true]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Delete failed'], 500);
        }
    }

    public function markHelpful(int $id): JsonResponse
    {
        try {
            $review = MedicalReview::findOrFail($id);

            $review->increment('helpful_count');

            Log::channel('audit')->info('Review marked helpful', ['review_id' => $review->id]);

            return response()->json(['success' => true, 'data' => $review]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Operation failed'], 500);
        }
    }

    public function all(): JsonResponse
    {
        try {
            $reviews = MedicalReview::paginate(50);

            return response()->json([
                'success' => true,
                'data' => $reviews,
                'correlation_id' => request()->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Failed to fetch reviews'], 500);
        }
    }

    public function approve(int $id): JsonResponse
    {
        try {
            $review = MedicalReview::findOrFail($id);

            $review->update(['status' => 'approved']);

            Log::channel('audit')->info('Review approved', ['review_id' => $review->id]);

            return response()->json(['success' => true, 'data' => $review]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Approval failed'], 500);
        }
    }

    public function reject(int $id): JsonResponse
    {
        try {
            $review = MedicalReview::findOrFail($id);

            $review->delete();

            Log::channel('audit')->info('Review rejected', ['review_id' => $review->id]);

            return response()->json(['success' => true]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Rejection failed'], 500);
        }
    }

    public function analytics(): JsonResponse
    {
        try {
            $reviews = MedicalReview::where('status', 'approved')->get();

            $analytics = [
                'total_reviews' => $reviews->count(),
                'average_rating' => $reviews->avg('rating'),
                'by_rating' => $reviews->groupBy('rating')->map->count(),
                'helpful_count' => $reviews->sum('helpful_count'),
            ];

            return response()->json([
                'success' => true,
                'data' => $analytics,
                'correlation_id' => request()->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Analytics failed'], 500);
        }
    }
}
