<?php declare(strict_types=1);

namespace App\Domains\Auto\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CarWashBookingController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly FraudControlService $fraudControlService,
        ) {}

        public function index(Request $request): JsonResponse
        {
            try {
                $correlationId = Str::uuid()->toString();

                $bookings = CarWashBooking::query()
                    ->where('tenant_id', tenant('id'))
                    ->paginate(15);

                return response()->json([
                    'success' => true,
                    'data' => $bookings,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка при получении броней',
                ], 500);
            }
        }

        public function store(Request $request): JsonResponse
        {
            $fraudResult = $this->fraudControlService->check(
                auth()->id() ?? 0,
                'operation',
                0,
                request()->ip(),
                request()->header('X-Device-Fingerprint'),
                $correlationId,
            );

            if ($fraudResult['decision'] === 'block') {
                Log::channel('fraud_alert')->warning('Operation blocked by fraud control', [
                    'correlation_id' => $correlationId,
                    'user_id'        => auth()->id(),
                    'score'          => $fraudResult['score'],
                ]);
                return response()->json([
                    'success'        => false,
                    'error'          => 'Операция заблокирована.',
                    'correlation_id' => $correlationId,
                ], 403);
            }

            try {
                $correlationId = Str::uuid()->toString();

                $request->validate([
                    'client_id' => 'required|exists:users,id',
                    'wash_type' => 'required|string',
                    'scheduled_at' => 'required|date_format:Y-m-d H:i:s',
                ]);

                $validated = $request->all();
                $booking = DB::transaction(function () use ($validated, $correlationId) {
                    $booking = CarWashBooking::create([
                        'tenant_id' => tenant('id'),
                        'client_id' => ($validated['client_id'] ?? null),
                        'wash_type' => ($validated['wash_type'] ?? null),
                        'scheduled_at' => ($validated['scheduled_at'] ?? null),
                        'status' => 'pending',
                        'price' => 50000,
                        'correlation_id' => $correlationId,
                    ]);

                    Log::channel('audit')->info('Car wash booking created', [
                        'booking_id' => $booking->id,
                        'correlation_id' => $correlationId,
                    ]);

                    return $booking;
                });

                return response()->json([
                    'success' => true,
                    'data' => $booking,
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка при создании брони',
                ], 500);
            }
        }

        public function show(CarWashBooking $booking): JsonResponse
        {
            return response()->json([
                'success' => true,
                'data' => $booking,
            ]);
        }

        public function cancel(CarWashBooking $booking): JsonResponse
        {
            try {
                $this->authorize('cancel', $booking);

                $booking->update(['status' => 'cancelled']);

                Log::channel('audit')->info('Car wash booking cancelled', [
                    'booking_id' => $booking->id,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Бронь отменена',
                ]);
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка при отмене брони',
                ], 500);
            }
        }

        public function availability(Request $request): JsonResponse
        {
            $washTypes = ['standard' => 'Стандартная', 'premium' => 'Премиум', 'express' => 'Экспресс'];

            return response()->json([
                'success' => true,
                'types' => $washTypes,
            ]);
        }

        public function washTypes(Request $request): JsonResponse
        {
            $types = [
                'standard' => ['name' => 'Стандартная мойка', 'price' => 50000, 'duration' => 30],
                'premium' => ['name' => 'Премиум мойка', 'price' => 80000, 'duration' => 45],
                'express' => ['name' => 'Экспресс мойка', 'price' => 35000, 'duration' => 20],
            ];

            return response()->json([
                'success' => true,
                'types' => $types,
            ]);
        }
}
