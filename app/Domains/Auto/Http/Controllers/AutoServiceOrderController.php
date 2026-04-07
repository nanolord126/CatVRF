<?php declare(strict_types=1);

namespace App\Domains\Auto\Http\Controllers;

use Carbon\Carbon;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class AutoServiceOrderController extends Controller
{

    public function __construct(private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

        public function index(Request $request): JsonResponse
        {
            try {
                $correlationId = Str::uuid()->toString();

                $orders = AutoServiceOrder::query()
                    ->where('tenant_id', tenant()->id)
                    ->with(['service', 'client'])
                    ->paginate(15);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $orders,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Ошибка при получении заказов',
                ], 500);
            }
        }

        public function store(Request $request): JsonResponse
        {
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            if ($fraudResult['decision'] === 'block') {
                $this->logger->warning('Operation blocked by fraud control', [
                    'correlation_id' => $correlationId,
                    'user_id'        => $request->user()?->id,
                    'score'          => $fraudResult['score'],
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success'        => false,
                    'error'          => 'Операция заблокирована.',
                    'correlation_id' => $correlationId,
                ], 403);
            }

            try {
                $correlationId = Str::uuid()->toString();

                $request->validate([
                    'client_id' => 'required|exists:users,id',
                    'car_brand' => 'required|string',
                    'car_model' => 'required|string',
                    'service_id' => 'nullable|exists:auto_services,id',
                    'appointment_datetime' => 'required|date_format:Y-m-d H:i:s',
                ]);

                $validated = $request->all();
                $order = $this->db->transaction(function () use ($validated, $correlationId) {
                    $order = AutoServiceOrder::create([
                        'tenant_id' => tenant()->id,
                        'client_id' => ($validated['client_id'] ?? null),
                        'car_brand' => ($validated['car_brand'] ?? null),
                        'car_model' => ($validated['car_model'] ?? null),
                        'service_id' => ($validated['service_id'] ?? null),
                        'appointment_datetime' => ($validated['appointment_datetime'] ?? null),
                        'status' => 'pending',
                        'total_price' => 0,
                        'correlation_id' => $correlationId,
                    ]);

                    $this->logger->info('Service order created', [
                        'order_id' => $order->id,
                        'correlation_id' => $correlationId,
                    ]);

                    return $order;
                });

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $order,
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Ошибка при создании заказа',
                ], 500);
            }
        }

        public function show(AutoServiceOrder $order): JsonResponse
        {
            return new \Illuminate\Http\JsonResponse([
                'success' => true,
                'data' => $order->load(['service', 'client']),
            ]);
        }

        public function cancel(AutoServiceOrder $order): JsonResponse
        {
            try {
                $this->authorize('cancel', $order);

                $order->update(['status' => 'cancelled']);

                $this->logger->info('Service order cancelled', [
                    'order_id' => $order->id,
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'message' => 'Заказ отменён',
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Ошибка при отмене заказа',
                ], 500);
            }
        }

        public function complete(AutoServiceOrder $order): JsonResponse
        {
            try {
                $order->update([
                    'status' => 'completed',
                    'completed_at' => Carbon::now(),
                ]);

                $this->logger->info('Service order completed', [
                    'order_id' => $order->id,
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'message' => 'Заказ завершён',
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Ошибка при завершении заказа',
                ], 500);
            }
        }

        public function listServices(Request $request): JsonResponse
        {
            $services = AutoService::query()
                ->where('tenant_id', tenant()->id)
                ->select(['id', 'name', 'price', 'duration_minutes'])
                ->get();

            return new \Illuminate\Http\JsonResponse([
                'success' => true,
                'data' => $services,
            ]);
        }
}
