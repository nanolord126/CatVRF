<?php declare(strict_types=1);

namespace App\Domains\Pharmacy\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class OrderController extends Controller
{

    public function __construct(private readonly PharmacyService $service,
            private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

        /**
         * Создание заказа лекарств (B2C).
         * POST /pharmacy/orders
         */
        public function store(PharmacyOrderStoreRequest $request): JsonResponse
        {
            $correlationId = (string) Str::uuid();

            try {
                return $this->db->transaction(function () use ($request, $correlationId) {
                    // 1. Fraud Check
                    $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'pharmacy_order', amount: 0, correlationId: $correlationId ?? '');

                    if ($fraudResult['decision'] === 'block') {
                        $this->logger->warning('Pharmacy order blocked by fraud check', [
                            'user_id' => $request->user()?->id,
                            'correlation_id' => $correlationId,
                            'score' => $fraudResult['score'],
                        ]);
                        return new \Illuminate\Http\JsonResponse(['error' => 'Заказ заблокирован системой безопасности'], 403);
                    }

                    // 2. Создание заказа через сервис
                    $order = $this->service->createOrder(
                        pharmacyId: $request->validated()['pharmacy_id'],
                        items: $request->validated()['items'],
                        correlationId: $correlationId
                    );

                    // 3. Audit Log
                    $this->logger->info('Pharmacy order created (B2C)', [
                        'order_id' => $order->id,
                        'user_id' => $request->user()?->id,
                        'total' => $order->total_price_kopecks,
                        'correlation_id' => $correlationId,
                        'timestamp' => Carbon::now(),
                    ]);

                    return new \Illuminate\Http\JsonResponse([
                        'status' => 'success',
                        'order_id' => $order->id,
                        'order_uuid' => $order->uuid,
                        'requires_prescription' => $order->requires_prescription,
                        'total_kopecks' => $order->total_price_kopecks,
                        'correlation_id' => $correlationId,
                    ], 201);
                });
            } catch (\Throwable $e) {
                $this->logger->error('Pharmacy order creation failed', [
                    'user_id' => $request->user()?->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                    'trace' => $e->getTraceAsString(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        /**
         * Получение статуса заказа.
         * GET /pharmacy/orders/{orderId}
         */
        public function show(int $orderId): JsonResponse
        {
            $correlationId = (string) Str::uuid();

            try {
                $order = $this->service->getOrder($orderId);

                if ($order->client_id !== $request->user()?->id && !$request->user()->isPharmacyOwner($order->pharmacy_id)) {
                    $this->logger->warning('Unauthorized pharmacy order access', [
                        'user_id' => $request->user()?->id,
                        'order_id' => $orderId,
                        'correlation_id' => $correlationId,
                    ]);
                    return new \Illuminate\Http\JsonResponse(['error' => 'Unauthorized'], 403);
                }

                return new \Illuminate\Http\JsonResponse([
                    'order_id' => $order->id,
                    'uuid' => $order->uuid,
                    'status' => $order->status,
                    'total_kopecks' => $order->total_price_kopecks,
                    'delivered_at' => $order->delivered_at,
                    'requires_prescription' => $order->requires_prescription,
                    'correlation_id' => $correlationId,
                ], 200);
            } catch (\Throwable $e) {
                $this->logger->error('Pharmacy order fetch failed', [
                    'order_id' => $orderId,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                return new \Illuminate\Http\JsonResponse(['error' => 'Order not found'], 404);
            }
        }

        /**
         * Отправка рецепта для заказа (для рецептурных препаратов).
         * POST /pharmacy/orders/{orderId}/prescription
         */
        public function uploadPrescription(int $orderId, PharmacyOrderStoreRequest $request): JsonResponse
        {
            $correlationId = (string) Str::uuid();

            try {
                return $this->db->transaction(function () use ($orderId, $request, $correlationId) {
                    $this->service->validatePrescription(
                        orderId: $orderId,
                        rxData: $request->validated()['prescription_data'] ?? '',
                        correlationId: $correlationId
                    );

                    $this->logger->info('Pharmacy prescription uploaded', [
                        'order_id' => $orderId,
                        'user_id' => $request->user()?->id,
                        'correlation_id' => $correlationId,
                    ]);

                    return new \Illuminate\Http\JsonResponse([
                        'status' => 'success',
                        'message' => 'Рецепт получен и обработан',
                        'correlation_id' => $correlationId,
                    ], 200);
                });
            } catch (\Throwable $e) {
                $this->logger->error('Pharmacy prescription validation failed', [
                    'order_id' => $orderId,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                return new \Illuminate\Http\JsonResponse(['error' => $e->getMessage()], 400);
            }
        }
}
