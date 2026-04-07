<?php declare(strict_types=1);

namespace App\Domains\Hotels\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class ReviewController extends Controller
{


    public function __construct(
            private readonly ReviewService $reviewService,
            private readonly FraudControlService $fraud, private readonly LoggerInterface $logger) {}

        public function index(string $hotelId): JsonResponse
        {
            try {
                $reviews = Review::where('hotel_id', $hotelId)
                    ->where('published_at', '!=', null)
                    ->orderByDesc('published_at')
                    ->paginate(10);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $reviews,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 500);
            }
        }

        public function store(\Illuminate\Http\Request $request, string $hotelId): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $this->authorize('create', Review::class);

                $data = $request->validate([
                    'booking_id' => 'nullable|uuid',
                    'rating' => 'required|integer|between:1,5',
                    'title' => 'required|string|max:255',
                    'content' => 'required|string',
                    'categories' => 'nullable|array',
                ]);

                $review = $this->reviewService->createReview(
                    hotelId: (int) $hotelId,
                    rating: $data['rating'],
                    title: $data['title'],
                    content: $data['content'],
                    categories: $data['categories'] ?? null,
                    correlationId: $correlationId,
                );

                $this->logger->info('Hotel review created', [
                    'correlation_id' => $correlationId,
                    'hotel_id' => $hotelId,
                    'user_id' => $request->user()?->id,
                    'rating' => $data['rating'],
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $review,
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 500);
            }
        }

        public function myReviews(\Illuminate\Http\Request $request): JsonResponse
        {
            try {
                $reviews = Review::where('guest_id', $request->user()?->id)
                    ->paginate(10);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $reviews,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 500);
            }
        }
}
