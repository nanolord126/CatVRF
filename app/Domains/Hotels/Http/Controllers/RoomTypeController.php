<?php declare(strict_types=1);

namespace App\Domains\Hotels\Http\Controllers;

use App\Domains\Hotels\Models\RoomType;
use App\Domains\Hotels\Services\PricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class RoomTypeController extends Controller
{
    public function __construct(
        private readonly PricingService $pricingService,
    ) {}

    public function index(string $hotelId): JsonResponse
    {
        try {
            $rooms = RoomType::where('hotel_id', $hotelId)->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $rooms,
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
            $this->authorize('create', RoomType::class);

            $data = request()->validate([
                'name' => 'required|string',
                'description' => 'nullable|string',
                'base_price_per_night' => 'required|integer',
                'max_guests' => 'required|integer',
                'available_count' => 'required|integer',
                'amenities' => 'nullable|array',
            ]);

            $room = RoomType::create([
                'tenant_id' => tenant('id'),
                'hotel_id' => $hotelId,
                ...$data,
                'correlation_id' => \Illuminate\Support\Str::uuid(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $room,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(string $id): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        try {
            $room = RoomType::findOrFail($id);
            $this->authorize('update', $room);

            $data = request()->validate([
                'name' => 'nullable|string',
                'base_price_per_night' => 'nullable|integer',
                'available_count' => 'nullable|integer',
                'amenities' => 'nullable|array',
            ]);

            $room->update($data);

            return response()->json([
                'success' => true,
                'data' => $room,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        try {
            $room = RoomType::findOrFail($id);
            $this->authorize('delete', $room);

            $room->delete();

            return response()->json([
                'success' => true,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function checkAvailability(string $hotelId): JsonResponse
    {
        try {
            $checkInDate = request()->input('check_in_date');
            $checkOutDate = request()->input('check_out_date');

            $available = $this->pricingService->getAvailableRooms(
                (int) $hotelId,
                $checkInDate,
                $checkOutDate,
            );

            return response()->json([
                'success' => true,
                'data' => $available,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
