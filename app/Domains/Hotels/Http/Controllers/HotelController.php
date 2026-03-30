<?php declare(strict_types=1);

namespace App\Domains\Hotels\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class HotelController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly BookingService $bookingService,
            private readonly PricingService $pricingService,
            private readonly FraudControlService $fraudControlService,
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
            $correlationId = Str::uuid()->toString();
            $fraudResult   = $this->fraudControlService->check(auth()->id() ?? 0, 'hotel_create', 0, request()->ip(), null, $correlationId);

            if ($fraudResult['decision'] === 'block') {
                Log::channel('fraud_alert')->warning('Hotel create blocked', [
                    'correlation_id' => $correlationId,
                    'user_id'        => auth()->id(),
                    'score'          => $fraudResult['score'],
                ]);
                return response()->json(['success' => false, 'error' => 'Операция заблокирована.', 'correlation_id' => $correlationId], 403);
            }

            Log::channel('audit')->info('Hotel create start', ['correlation_id' => $correlationId, 'user_id' => auth()->id()]);

            try {
                $data = request()->validate([
                    'name'        => 'required|string',
                    'address'     => 'required|string',
                    'star_rating' => 'required|integer|between:1,5',
                    'total_rooms' => 'required|integer',
                    'description' => 'nullable|string',
                    'amenities'   => 'nullable|array',
                ]);

                $hotel = Hotel::create([
                    'tenant_id'      => tenant('id'),
                    ...$data,
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('Hotel created', [
                    'correlation_id' => $correlationId,
                    'user_id'        => auth()->id(),
                    'hotel_id'       => $hotel->id,
                ]);

                return response()->json(['success' => true, 'data' => $hotel, 'correlation_id' => $correlationId], 201);
            } catch (\Throwable $e) {
                Log::error('Hotel create failed', ['correlation_id' => $correlationId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
            }
        }

        public function update(string $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $fraudResult   = $this->fraudControlService->check(auth()->id() ?? 0, 'hotel_update', 0, request()->ip(), null, $correlationId);

            if ($fraudResult['decision'] === 'block') {
                Log::channel('fraud_alert')->warning('Hotel update blocked', [
                    'correlation_id' => $correlationId,
                    'user_id'        => auth()->id(),
                    'score'          => $fraudResult['score'],
                ]);
                return response()->json(['success' => false, 'error' => 'Операция заблокирована.', 'correlation_id' => $correlationId], 403);
            }

            try {
                $hotel = Hotel::findOrFail($id);
                $this->authorize('update', $hotel);

                $before = $hotel->toArray();

                $data = request()->validate([
                    'name'        => 'nullable|string',
                    'address'     => 'nullable|string',
                    'star_rating' => 'nullable|integer|between:1,5',
                    'description' => 'nullable|string',
                    'amenities'   => 'nullable|array',
                ]);

                $hotel->update($data);

                Log::channel('audit')->info('Hotel updated', [
                    'correlation_id' => $correlationId,
                    'user_id'        => auth()->id(),
                    'hotel_id'       => $id,
                    'before'         => $before,
                    'after'          => $hotel->fresh()->toArray(),
                ]);

                return response()->json(['success' => true, 'data' => $hotel, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                Log::error('Hotel update failed', ['correlation_id' => $correlationId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
            }
        }

        public function destroy(string $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $fraudResult   = $this->fraudControlService->check(auth()->id() ?? 0, 'hotel_delete', 0, request()->ip(), null, $correlationId);

            if ($fraudResult['decision'] === 'block') {
                Log::channel('fraud_alert')->warning('Hotel destroy blocked', [
                    'correlation_id' => $correlationId,
                    'user_id'        => auth()->id(),
                    'score'          => $fraudResult['score'],
                ]);
                return response()->json(['success' => false, 'error' => 'Операция заблокирована.', 'correlation_id' => $correlationId], 403);
            }

            try {
                $hotel = Hotel::findOrFail($id);
                $this->authorize('delete', $hotel);

                $hotel->delete();

                Log::channel('audit')->info('Hotel deleted', [
                    'correlation_id' => $correlationId,
                    'user_id'        => auth()->id(),
                    'hotel_id'       => $id,
                ]);

                return response()->json(['success' => true, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                Log::error('Hotel destroy failed', ['correlation_id' => $correlationId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
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
            $correlationId = Str::uuid()->toString();
            $fraudResult   = $this->fraudControlService->check(auth()->id() ?? 0, 'hotel_verify', 0, request()->ip(), null, $correlationId);

            if ($fraudResult['decision'] === 'block') {
                Log::channel('fraud_alert')->warning('Hotel verify blocked', [
                    'correlation_id' => $correlationId,
                    'user_id'        => auth()->id(),
                    'score'          => $fraudResult['score'],
                ]);
                return response()->json(['success' => false, 'error' => 'Операция заблокирована.', 'correlation_id' => $correlationId], 403);
            }

            try {
                $hotel = Hotel::findOrFail($id);
                $this->authorize('delete', $hotel);

                $hotel->update(['is_verified' => true]);

                Log::channel('audit')->info('Hotel verified', [
                    'correlation_id' => $correlationId,
                    'user_id'        => auth()->id(),
                    'hotel_id'       => $id,
                ]);

                return response()->json(['success' => true, 'data' => $hotel, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                Log::error('Hotel verify failed', ['correlation_id' => $correlationId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
            }
        }
}
