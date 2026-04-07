<?php declare(strict_types=1);

namespace App\Domains\SportsNutrition\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class VapeOrderController extends Controller
{

    /**
         * Конструктор с DP.
         */
        public function __construct(
            private VapeOrderService $orderService,
            private VapeAgeVerificationService $ageVerifier, private readonly LoggerInterface $logger) {}

        /**
         * Создать новый заказ на устройства или жидкости.
         * Эндпоинт защищен 18+ гейтом внутри сервиса.
         */
        public function store(VapeOrderRequest $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID') ?? (string) Str::uuid();

            try {
                // 1. Создаем заказ через сервис
                $order = $this->orderService->createOrder(
                    userId: $request->user()?->id,
                    params: $request->validated(),
                    correlationId: $correlationId,
                );

                // 2. Audit log
                $this->logger->info('Vape order controller: created', [
                    'order_uuid' => $order->uuid,
                    'user_id' => $request->user()?->id,
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'order' => $order,
                    'correlation_id' => $correlationId,
                ], 201);

            } catch (Throwable $e) {

                // 3. Error Log + Trace
                $this->logger->error('Vape order controller error store', [
                    'user_id' => $request->user()?->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Internal error creating vape order: ' . $e->getMessage(),
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        /**
         * Получить информацию о заказе.
         */
        public function show(string $uuid): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID') ?? (string) Str::uuid();

            try {
                // 4. Глобальный теннант-скопинг уже применен в модели VapeOrder::booted()
                $order = VapeOrder::where('uuid', $uuid)->firstOrFail();

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'order' => $order,
                    'correlation_id' => $correlationId,
                ]);

            } catch (Throwable $e) {

                $this->logger->warning('Vape order not found show', [
                    'order_uuid' => $uuid,
                    'user_id' => $request->user()?->id,
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Order not found',
                    'correlation_id' => $correlationId,
                ], 404);
            }
        }

        /**
         * Отмена заказа. Возвращает средства в Wallet, если оплачен.
         */
        public function cancel(string $uuid): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID') ?? (string) Str::uuid();

            try {
                $this->orderService->cancelOrder($uuid, 'Cancelled by user', $correlationId);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'message' => 'Order cancelled successfully',
                    'correlation_id' => $correlationId,
                ]);

            } catch (Throwable $e) {

                $this->logger->error('Vape order cancel failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ], 400);
            }
        }
}
