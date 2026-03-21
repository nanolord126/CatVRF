<?php declare(strict_types=1);

namespace App\Domains\Freelance\Http\Controllers;

use App\Domains\Freelance\Models\FreelanceReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class ReviewController
{
    public function store(Request $request): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        try {
            $correlationId = Str::uuid();

            return DB::transaction(function () use ($request, $correlationId) {
                $review = FreelanceReview::create([
                    'tenant_id' => tenant()->id,
                    'contract_id' => $request->input('contract_id'),
                    'reviewer_id' => auth()->id(),
                    'freelancer_id' => $request->input('freelancer_id'),
                    'client_id' => $request->input('client_id'),
                    'review_type' => $request->input('review_type', 'client_to_freelancer'),
                    'communication_rating' => $request->input('communication_rating'),
                    'work_quality_rating' => $request->input('work_quality_rating'),
                    'timeliness_rating' => $request->input('timeliness_rating'),
                    'overall_rating' => $request->input('overall_rating'),
                    'comment' => $request->input('comment'),
                    'review_aspects' => $request->input('review_aspects', []),
                    'verified_contract' => true,
                    'would_hire_again' => $request->input('would_hire_again'),
                    'status' => 'approved',
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('Freelance review submitted', [
                    'review_id' => $review->id,
                    'contract_id' => $request->input('contract_id'),
                    'overall_rating' => $request->input('overall_rating'),
                    'correlation_id' => $correlationId,
                ]);

                return response()->json([
                    'success' => true,
                    'data' => $review,
                    'correlation_id' => $correlationId,
                ], 201);
            });
        } catch (\Exception $e) {
            Log::channel('audit')->error('Error submitting review', [
                'error' => $e->getMessage(),
                'correlation_id' => Str::uuid(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit review',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function freelancerReviews(int $id): JsonResponse
    {
        try {
            $reviews = FreelanceReview::where('freelancer_id', $id)
                ->where('status', 'approved')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $reviews,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Exception $e) {
            Log::channel('audit')->error('Error listing freelancer reviews', [
                'freelancer_id' => $id,
                'error' => $e->getMessage(),
                'correlation_id' => Str::uuid(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to list reviews',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function contractReviews(int $id): JsonResponse
    {
        try {
            $reviews = FreelanceReview::where('contract_id', $id)->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $reviews,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Exception $e) {
            Log::channel('audit')->error('Error listing contract reviews', [
                'contract_id' => $id,
                'error' => $e->getMessage(),
                'correlation_id' => Str::uuid(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to list reviews',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function markHelpful(int $id): JsonResponse
    {
        try {
            $correlationId = Str::uuid();
            $review = FreelanceReview::findOrFail($id);

            $review->increment('helpful_count');

            Log::channel('audit')->info('Review marked as helpful', [
                'review_id' => $id,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Exception $e) {
            Log::channel('audit')->error('Error marking review as helpful', [
                'review_id' => $id,
                'error' => $e->getMessage(),
                'correlation_id' => Str::uuid(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark review',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function markUnhelpful(int $id): JsonResponse
    {
        try {
            $correlationId = Str::uuid();
            $review = FreelanceReview::findOrFail($id);

            $review->increment('unhelpful_count');

            Log::channel('audit')->info('Review marked as unhelpful', [
                'review_id' => $id,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Exception $e) {
            Log::channel('audit')->error('Error marking review as unhelpful', [
                'review_id' => $id,
                'error' => $e->getMessage(),
                'correlation_id' => Str::uuid(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark review',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }
}
