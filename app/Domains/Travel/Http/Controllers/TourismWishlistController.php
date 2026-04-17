<?php declare(strict_types=1);

namespace App\Domains\Travel\Http\Controllers;

use App\Domains\Travel\Services\TourismWishlistService;
use App\Domains\Travel\Http\Resources\TourismWishlistResource;
use App\Domains\Travel\Http\Resources\TourismWishlistCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use Illuminate\Support\Str;

/**
 * Tourism Wishlist Controller
 * 
 * API controller for tourism wishlist operations.
 * Handles adding/removing items from wishlist with AI-powered recommendations.
 */
final class TourismWishlistController
{
    public function __construct(
        private readonly TourismWishlistService $wishlistService,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Add tour to wishlist with preferences.
     */
    public function store(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $validated = $request->validate([
            'tour_id' => ['required', 'integer', 'exists:tours,id'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:10'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'budget_range' => ['nullable', 'array', 'min:2'],
            'budget_range.*' => ['numeric', 'min:0'],
            'preferred_dates' => ['nullable', 'array'],
            'preferred_dates.*' => ['string', 'date'],
            'group_size' => ['nullable', 'integer', 'min:1', 'max:50'],
            'special_requests' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $wishlistItem = $this->wishlistService->addToWishlist(
                userId: (int) auth()->id(),
                tourId: (int) $validated['tour_id'],
                preferences: [
                    'priority' => $validated['priority'] ?? 5,
                    'notes' => $validated['notes'] ?? null,
                    'budget_range' => $validated['budget_range'] ?? null,
                    'preferred_dates' => $validated['preferred_dates'] ?? null,
                    'group_size' => $validated['group_size'] ?? null,
                    'special_requests' => $validated['special_requests'] ?? null,
                    'added_from' => 'api',
                ],
                correlationId: $correlationId,
            );

            $this->logger->info('Tour added to wishlist via API', [
                'wishlist_id' => $wishlistItem->id,
                'user_id' => auth()->id(),
                'tour_id' => $validated['tour_id'],
                'correlation_id' => $correlationId,
            ]);

            return (new TourismWishlistResource($wishlistItem))
                ->additional([
                    'message' => 'Tour added to wishlist',
                    'recommendations' => $this->wishlistService->getRecommendationsFromWishlist((int) auth()->id(), $correlationId),
                    'correlation_id' => $correlationId,
                ])
                ->response()
                ->setStatusCode(201);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to add tour to wishlist', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'error' => 'Failed to add tour to wishlist',
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * Get user wishlist with AI recommendations.
     */
    public function index(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            $wishlistData = $this->wishlistService->getUserWishlist((int) auth()->id(), $correlationId);

            return response()->json([
                'wishlist_items' => $wishlistData['wishlist_items'],
                'recommendations' => $wishlistData['recommendations'],
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to get wishlist', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'error' => 'Failed to get wishlist',
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * Remove tour from wishlist.
     */
    public function destroy(string $uuid, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            $this->wishlistService->removeFromWishlist($uuid, $correlationId);

            $this->logger->info('Tour removed from wishlist via API', [
                'wishlist_uuid' => $uuid,
                'user_id' => auth()->id(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'message' => 'Tour removed from wishlist',
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to remove tour from wishlist', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'error' => 'Failed to remove tour from wishlist',
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * Get recommendations based on wishlist.
     */
    public function recommendations(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            $recommendations = $this->wishlistService->getRecommendationsFromWishlist((int) auth()->id(), $correlationId);

            return response()->json([
                'recommendations' => $recommendations,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to get wishlist recommendations', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'error' => 'Failed to get recommendations',
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * Get wishlist-based discount for booking.
     */
    public function discount(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $validated = $request->validate([
            'tour_id' => ['required', 'integer', 'exists:tours,id'],
        ]);

        try {
            $discountRate = $this->wishlistService->getWishlistDiscount(
                userId: (int) auth()->id(),
                tourId: (int) $validated['tour_id'],
                correlationId: $correlationId,
            );

            return response()->json([
                'discount_rate' => $discountRate,
                'discount_percentage' => $discountRate * 100,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to get wishlist discount', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'error' => 'Failed to get discount',
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
}
