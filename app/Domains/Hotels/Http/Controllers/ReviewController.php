<?php declare(strict_types=1);

namespace App\Domains\Hotels\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ReviewController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly ReviewService $reviewService,
            private readonly FraudControlService $fraudControlService,
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
            $correlationId = Str::uuid()->toString();
            $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

            try {
                $this->authorize('create', Review::class);

                $data = request()->validate([
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

                Log::channel('audit')->info('Hotel review created', [
                    'correlation_id' => $correlationId,
                    'hotel_id' => $hotelId,
                    'user_id' => auth()->id(),
                    'rating' => $data['rating'],
                ]);

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
