<?php declare(strict_types=1);

namespace App\Domains\Hotels\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class HotelController extends Controller
{


    public function __construct(
            private readonly BookingService $bookingService,
            private readonly PricingService $pricingService,
            private readonly FraudControlService $fraud, private readonly LoggerInterface $logger) {}

        public function index(): JsonResponse
        {
            try {
                $hotels = Hotel::with('roomTypes')
                    ->where('status', 'active')
                    ->paginate(15);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $hotels,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
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

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $hotel->load(['roomTypes', 'images', 'reviews']),
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 500);
            }
        }

        public function store(\Illuminate\Http\Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $fraudResult = $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'hotel_create', amount: 0, correlationId: $correlationId ?? '');

            if ($fraudResult['decision'] === 'block') {
                $this->logger->warning('Hotel create blocked', [
                    'correlation_id' => $correlationId,
                    'user_id'        => $request->user()?->id,
                    'score'          => $fraudResult['score'],
                ]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Операция заблокирована.', 'correlation_id' => $correlationId], 403);
            }

            $this->logger->info('Hotel create start', ['correlation_id' => $correlationId, 'user_id' => $request->user()?->id]);

            try {
                $data = $request->validate([
                    'name'        => 'required|string',
                    'address'     => 'required|string',
                    'star_rating' => 'required|integer|between:1,5',
                    'total_rooms' => 'required|integer',
                    'description' => 'nullable|string',
                    'amenities'   => 'nullable|array',
                ]);

                $hotel = Hotel::create([
                    'tenant_id'      => tenant()->id,
                    ...$data,
                    'correlation_id' => $correlationId,
                ]);

                $this->logger->info('Hotel created', [
                    'correlation_id' => $correlationId,
                    'user_id'        => $request->user()?->id,
                    'hotel_id'       => $hotel->id,
                ]);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $hotel, 'correlation_id' => $correlationId], 201);
            } catch (\Throwable $e) {
                $this->logger->error('Hotel create failed', ['correlation_id' => $correlationId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
            }
        }

        public function update(\Illuminate\Http\Request $request, string $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $fraudResult = $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'hotel_update', amount: 0, correlationId: $correlationId ?? '');

            if ($fraudResult['decision'] === 'block') {
                $this->logger->warning('Hotel update blocked', [
                    'correlation_id' => $correlationId,
                    'user_id'        => $request->user()?->id,
                    'score'          => $fraudResult['score'],
                ]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Операция заблокирована.', 'correlation_id' => $correlationId], 403);
            }

            try {
                $hotel = Hotel::findOrFail($id);
                $this->authorize('update', $hotel);

                $before = $hotel->toArray();

                $data = $request->validate([
                    'name'        => 'nullable|string',
                    'address'     => 'nullable|string',
                    'star_rating' => 'nullable|integer|between:1,5',
                    'description' => 'nullable|string',
                    'amenities'   => 'nullable|array',
                ]);

                $hotel->update($data);

                $this->logger->info('Hotel updated', [
                    'correlation_id' => $correlationId,
                    'user_id'        => $request->user()?->id,
                    'hotel_id'       => $id,
                    'before'         => $before,
                    'after'          => $hotel->fresh()->toArray(),
                ]);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $hotel, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                $this->logger->error('Hotel update failed', ['correlation_id' => $correlationId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
            }
        }

        public function destroy(\Illuminate\Http\Request $request, string $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $fraudResult = $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'hotel_delete', amount: 0, correlationId: $correlationId ?? '');

            if ($fraudResult['decision'] === 'block') {
                $this->logger->warning('Hotel destroy blocked', [
                    'correlation_id' => $correlationId,
                    'user_id'        => $request->user()?->id,
                    'score'          => $fraudResult['score'],
                ]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Операция заблокирована.', 'correlation_id' => $correlationId], 403);
            }

            try {
                $hotel = Hotel::findOrFail($id);
                $this->authorize('delete', $hotel);

                $hotel->delete();

                $this->logger->info('Hotel deleted', [
                    'correlation_id' => $correlationId,
                    'user_id'        => $request->user()?->id,
                    'hotel_id'       => $id,
                ]);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                $this->logger->error('Hotel destroy failed', ['correlation_id' => $correlationId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
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

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => [
                        'total_revenue' => $totalRevenue,
                        'total_commission' => $totalCommission,
                        'net_revenue' => $netRevenue,
                        'bookings_count' => $bookings->count(),
                    ],
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 500);
            }
        }

        public function verify(\Illuminate\Http\Request $request, string $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $fraudResult = $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'hotel_verify', amount: 0, correlationId: $correlationId ?? '');

            if ($fraudResult['decision'] === 'block') {
                $this->logger->warning('Hotel verify blocked', [
                    'correlation_id' => $correlationId,
                    'user_id'        => $request->user()?->id,
                    'score'          => $fraudResult['score'],
                ]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Операция заблокирована.', 'correlation_id' => $correlationId], 403);
            }

            try {
                $hotel = Hotel::findOrFail($id);
                $this->authorize('delete', $hotel);

                $hotel->update(['is_verified' => true]);

                $this->logger->info('Hotel verified', [
                    'correlation_id' => $correlationId,
                    'user_id'        => $request->user()?->id,
                    'hotel_id'       => $id,
                ]);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $hotel, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                $this->logger->error('Hotel verify failed', ['correlation_id' => $correlationId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
            }
        }
}
