<?php declare(strict_types=1);

namespace App\Domains\Hotels\Http\Controllers;

use App\Domains\Hotels\Models\Hotel;
use App\Domains\Hotels\Services\BookingService;
use App\Domains\Hotels\Services\PricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class HotelController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService,
        private readonly PricingService $pricingService,
    ) {}

    public function index(): JsonResponse
    {
        try {
            $hotels = Hotel::with('roomTypes')
                ->where('status', 'active')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $hotels,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $hotel = Hotel::findOrFail($id);
            $this->authorize('view', $hotel);

            return response()->json([
                'success' => true,
                'data' => $hotel->load(['roomTypes', 'images', 'reviews']),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        try {
            $this->authorize('create', Hotel::class);

            $data = request()->validate([
                'name' => 'required|string',
                'address' => 'required|string',
                'star_rating' => 'required|integer|between:1,5',
                'total_rooms' => 'required|integer',
                'description' => 'nullable|string',
                'amenities' => 'nullable|array',
            ]);

            $hotel = Hotel::create([
                'tenant_id' => tenant('id'),
                ...$data,
                'correlation_id' => \Illuminate\Support\Str::uuid(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $hotel,
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
            $hotel = Hotel::findOrFail($id);
            $this->authorize('update', $hotel);

            $data = request()->validate([
                'name' => 'nullable|string',
                'address' => 'nullable|string',
                'star_rating' => 'nullable|integer|between:1,5',
                'description' => 'nullable|string',
                'amenities' => 'nullable|array',
            ]);

            $hotel->update($data);

            return response()->json([
                'success' => true,
                'data' => $hotel,
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
            $hotel = Hotel::findOrFail($id);
            $this->authorize('delete', $hotel);

            $hotel->delete();

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

    public function revenue(string $id): JsonResponse
    {
        try {
            $hotel = Hotel::findOrFail($id);
            $this->authorize('view', $hotel);

            $bookings = $hotel->bookings()->where('payment_status', 'paid')->get();

            $totalRevenue = $bookings->sum('subtotal_price');
            $totalCommission = $bookings->sum('commission_price');
            $netRevenue = $totalRevenue - $totalCommission;

            return response()->json([
                'success' => true,
                'data' => [
                    'total_revenue' => $totalRevenue,
                    'total_commission' => $totalCommission,
                    'net_revenue' => $netRevenue,
                    'bookings_count' => $bookings->count(),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function verify(string $id): JsonResponse
    {
        try {
            $hotel = Hotel::findOrFail($id);
            $this->authorize('delete', $hotel);

            $hotel->update(['is_verified' => true]);

            return response()->json([
                'success' => true,
                'data' => $hotel,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
