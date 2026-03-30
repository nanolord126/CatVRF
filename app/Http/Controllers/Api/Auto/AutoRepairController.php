<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\Auto;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AutoRepairController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private AutoRepairService $repairService
        ) {}
        /**
         * Создание нового заказ-наряда.
         *
         * POST /api/v1/auto/repair/order
         */
        public function createOrder(Request $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
            try {
                Log::channel('audit')->info('Repair Order Request Received', [
                    'ip' => $request->ip(),
                    'correlation_id' => $correlationId,
                    'data' => $request->except(['client_complaint']), // Sensetive data filter
                ]);
                // 1. Валидация (в реальном проекте через AutoRepairOrderRequest)
                $validated = $request->validate([
                    'auto_vehicle_id' => 'required|integer|exists:auto_vehicles,id',
                    'client_id' => 'required|integer',
                    'planned_at' => 'required|date|after:now',
                    'client_complaint' => 'nullable|string|max:1000',
                ]);
                // 2. Создание в Service Layer
                $order = $this->repairService->createRepairOrder($validated, $correlationId);
                return response()->json([
                    'success' => true,
                    'data' => $order,
                    'meta' => [
                        'uuid' => $order->uuid,
                        'correlation_id' => $correlationId,
                        'status' => 'pending',
                    ],
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Repair Order Creation Failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Не удалось создать заказ-наряд. Ошибка: ' . $e->getMessage(),
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
}
