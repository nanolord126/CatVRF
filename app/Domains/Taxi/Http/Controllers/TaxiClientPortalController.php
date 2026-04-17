<?php declare(strict_types=1);

namespace App\Domains\Taxi\Http\Controllers;

use App\Domains\Taxi\Services\TaxiClientPortalService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Carbon\Carbon;

final class TaxiClientPortalController extends Controller
{
    public function __construct(
        private readonly TaxiClientPortalService $clientPortalService,
    ) {}

    public function getDashboard(Request $request, int $userId): JsonResponse
    {
        $dashboard = $this->clientPortalService->getClientDashboard(
            userId: $userId,
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'dashboard' => $dashboard,
        ]);
    }

    public function getRideHistory(Request $request, int $userId): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $history = $this->clientPortalService->getClientRideHistory(
            userId: $userId,
            startDate: $validated['start_date'] ?? null,
            endDate: $validated['end_date'] ?? null,
            perPage: $validated['per_page'] ?? 20,
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'history' => $history,
        ]);
    }

    public function addFavoriteLocation(Request $request, int $userId): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'is_default' => 'nullable|boolean',
            'metadata' => 'nullable|array',
            'tags' => 'nullable|array',
        ]);

        $favorite = $this->clientPortalService->addFavoriteLocation(
            userId: $userId,
            data: $validated,
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'favorite' => $favorite,
        ]);
    }

    public function addFavoriteDriver(Request $request, int $userId, int $driverId): JsonResponse
    {
        $favorite = $this->clientPortalService->addFavoriteDriver(
            userId: $userId,
            driverId: $driverId,
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'favorite' => $favorite,
        ]);
    }

    public function removeFavorite(Request $request, int $userId, string $uuid): JsonResponse
    {
        $this->clientPortalService->removeFavorite(
            userId: $userId,
            uuid: $uuid,
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'message' => 'Favorite removed',
        ]);
    }

    public function getStatistics(Request $request, int $userId): JsonResponse
    {
        $statistics = $this->clientPortalService->getClientStatistics(
            userId: $userId,
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'statistics' => $statistics,
        ]);
    }

    public function rateRide(Request $request, int $userId, string $rideUuid): JsonResponse
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        $ride = $this->clientPortalService->rateRide(
            userId: $userId,
            rideUuid: $rideUuid,
            rating: $validated['rating'],
            comment: $validated['comment'] ?? null,
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'ride' => $ride,
        ]);
    }

    public function getRideDetails(Request $request, int $userId, string $rideUuid): JsonResponse
    {
        $details = $this->clientPortalService->getRideDetails(
            userId: $userId,
            rideUuid: $rideUuid,
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'details' => $details,
        ]);
    }
}
