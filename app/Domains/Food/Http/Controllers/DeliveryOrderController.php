<?php declare(strict_types=1);

namespace App\Domains\Food\Http\Controllers;

use Carbon\Carbon;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class DeliveryOrderController extends Controller
{

    public function __construct(
            private readonly FraudControlService $fraud, private readonly LoggerInterface $logger) {}

        public function index(): JsonResponse
        {
            try {
                $deliveries = DeliveryOrder::query()
                    ->whereHas('order', fn ($q) => $q->where('client_id', $request->user()?->id))
                    ->with('order')
                    ->paginate(15);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $deliveries,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('DeliveryOrder index failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка'], 500);
            }
        }

        public function show(DeliveryOrder $delivery): JsonResponse
        {
            $this->authorize('view', $delivery);

            return new \Illuminate\Http\JsonResponse([
                'success' => true,
                'data' => $delivery->load('order'),
            ]);
        }

        public function start(DeliveryOrder $delivery): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            $fraudResult = $this->fraud->check(
                userId: $request->user()?->id ?? 0,
                operationType: 'delivery_start',
                amount: 0,
                ipAddress: $request->ip(),
                deviceFingerprint: null,
                correlationId: $correlationId,
            );

            if ($fraudResult['decision'] === 'block') {
                $this->logger->warning('Delivery start blocked by fraud control', [
                    'correlation_id' => $correlationId,
                    'delivery_id' => $delivery->id,
                    'score' => $fraudResult['score'],
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'error' => 'Операция заблокирована.',
                    'correlation_id' => $correlationId,
                ], 403);
            }

            try {
                $before = $delivery->status;

                $delivery->update([
                    'status' => 'on_way',
                    'picked_up_at' => Carbon::now(),
                ]);

                $this->logger->info('Delivery started', [
                    'correlation_id' => $correlationId,
                    'delivery_id' => $delivery->id,
                    'tenant_id' => $delivery->tenant_id ?? null,
                    'before' => $before,
                    'after' => 'on_way',
                    'user_id' => $request->user()?->id,
                ]);

                event(new \App\Domains\Food\Events\DeliveryStarted($delivery, $correlationId));

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'message' => 'Доставка начата',
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Delivery start failed', [
                    'correlation_id' => $correlationId,
                    'delivery_id' => $delivery->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'correlation_id' => $correlationId], 500);
            }
        }

        public function track(DeliveryOrder $delivery): JsonResponse
        {
            $this->authorize('track', $delivery);

            return new \Illuminate\Http\JsonResponse([
                'success' => true,
                'status' => $delivery->status,
                'location' => $delivery->delivery_point,
                'eta' => $delivery->eta_minutes,
            ]);
        }
}
