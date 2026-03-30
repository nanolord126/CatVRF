<?php declare(strict_types=1);

namespace App\Domains\Hotels\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class RoomTypeController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly PricingService $pricingService,
            private readonly FraudControlService $fraudControlService,
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
            $correlationId = Str::uuid()->toString();
            $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

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

                Log::channel('audit')->info('RoomType created', [
                    'correlation_id' => $correlationId,
                    'room_type_id' => $room->id,
                    'hotel_id' => $hotelId,
                    'user_id' => auth()->id(),
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
            $correlationId = Str::uuid()->toString();
            $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

            try {
                $room = RoomType::findOrFail($id);
                $this->authorize('update', $room);

                $data = request()->validate([
                    'name' => 'nullable|string',
                    'base_price_per_night' => 'nullable|integer',
                    'available_count' => 'nullable|integer',
                    'amenities' => 'nullable|array',
                ]);

                $before = $room->getAttributes();
                $room->update($data);

                Log::channel('audit')->info('RoomType updated', [
                    'correlation_id' => $correlationId,
                    'room_type_id' => $room->id,
                    'user_id' => auth()->id(),
                    'before' => $before,
                    'after' => $data,
                ]);

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
            $correlationId = Str::uuid()->toString();
            $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

            try {
                $room = RoomType::findOrFail($id);
                $this->authorize('delete', $room);

                $room->delete();

                Log::channel('audit')->info('RoomType deleted', [
                    'correlation_id' => $correlationId,
                    'room_type_id' => $room->id,
                    'user_id' => auth()->id(),
                ]);

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
