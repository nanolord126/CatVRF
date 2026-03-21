<?php declare(strict_types=1);

namespace App\Domains\Hotels\Http\Controllers;

use App\Domains\Hotels\Models\Review;
use App\Domains\Hotels\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class ReviewController extends Controller
{
    public function __construct(
        private readonly ReviewService $reviewService,
    ) {}

    public function index(string $hotelId): JsonResponse
    {
        try {
            $reviews = Review::where('hotel_id', $hotelId)
                ->where('published_at', '!=', null)
                ->orderByDesc('published_at')
                ->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $reviews,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(string $hotelId): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        try {
            $this->authorize('create', Review::class);

            $data = request()->validate([
                'booking_id' => 'nullable|uuid',
                'rating' => 'required|integer|between:1,5',
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'categories' => 'nullable|array',
            ]);

            $correlationId = \Illuminate\Support\Str::uuid();

            $review = $this->reviewService->createReview(
                hotelId: (int) $hotelId,
                rating: $data['rating'],
                title: $data['title'],
                content: $data['content'],
                categories: $data['categories'] ?? null,
                correlationId: $correlationId,
            );

            return response()->json([
                'success' => true,
                'data' => $review,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function myReviews(): JsonResponse
    {
        try {
            $reviews = Review::where('guest_id', auth()->id())
                ->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $reviews,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
