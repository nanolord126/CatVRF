<?php declare(strict_types=1);

namespace App\Domains\Hotels\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class RoomTypeController extends Controller
{


    public function __construct(
            private readonly PricingService $pricingService,
            private readonly FraudControlService $fraud, private readonly LoggerInterface $logger) {}

        public function index(string $hotelId): JsonResponse
        {
            try {
                $rooms = RoomType::where('hotel_id', $hotelId)->paginate(10);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $rooms,
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
                $this->authorize('create', RoomType::class);

                $data = $request->validate([
                    'name' => 'required|string',
                    'description' => 'nullable|string',
                    'base_price_per_night' => 'required|integer',
                    'max_guests' => 'required|integer',
                    'available_count' => 'required|integer',
                    'amenities' => 'nullable|array',
                ]);

                $room = RoomType::create([
                    'tenant_id' => tenant()->id,
                    'hotel_id' => $hotelId,
                    ...$data,
                    'correlation_id' => \Illuminate\Support\Str::uuid(),
                ]);

                $this->logger->info('RoomType created', [
                    'correlation_id' => $correlationId,
                    'room_type_id' => $room->id,
                    'hotel_id' => $hotelId,
                    'user_id' => $request->user()?->id,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $room,
                ], 201);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 500);
            }
        }

        public function update(\Illuminate\Http\Request $request, string $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $room = RoomType::findOrFail($id);
                $this->authorize('update', $room);

                $data = $request->validate([
                    'name' => 'nullable|string',
                    'base_price_per_night' => 'nullable|integer',
                    'available_count' => 'nullable|integer',
                    'amenities' => 'nullable|array',
                ]);

                $before = $room->getAttributes();
                $room->update($data);

                $this->logger->info('RoomType updated', [
                    'correlation_id' => $correlationId,
                    'room_type_id' => $room->id,
                    'user_id' => $request->user()?->id,
                    'before' => $before,
                    'after' => $data,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $room,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 500);
            }
        }

        public function destroy(\Illuminate\Http\Request $request, string $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $room = RoomType::findOrFail($id);
                $this->authorize('delete', $room);

                $room->delete();

                $this->logger->info('RoomType deleted', [
                    'correlation_id' => $correlationId,
                    'room_type_id' => $room->id,
                    'user_id' => $request->user()?->id,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 500);
            }
        }

        public function checkAvailability(\Illuminate\Http\Request $request, string $hotelId): JsonResponse
        {
            try {
                $checkInDate = $request->input('check_in_date');
                $checkOutDate = $request->input('check_out_date');

                $available = $this->pricingService->getAvailableRooms(
                    (int) $hotelId,
                    $checkInDate,
                    $checkOutDate,
                );

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $available,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 500);
            }
        }
}
