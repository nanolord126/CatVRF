<?php declare(strict_types=1);

namespace App\Domains\Food\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;
use App\Services\FraudControlService;
use App\Domains\Food\Models\DeliveryOrder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

final class DeliveryOrderController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
            private readonly FraudControlService $fraud, private readonly LoggerInterface $logger) {}

        public function index(Request $request): JsonResponse
        {
            try {
                $deliveries = DeliveryOrder::query()
                    ->whereHas('order', fn ($q) => $q->where('customer_id', $request->user()?->id))
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

        public function show(Request $request, DeliveryOrder $delivery): JsonResponse
    {
        $this->authorize('view', $delivery);

        return new \Illuminate\Http\JsonResponse([
            'success' => true,
            'data' => $delivery->load('order'),
        ]);
    }

        public function start(Request $request, DeliveryOrder $delivery): JsonResponse
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

        public function track(Request $request, DeliveryOrder $delivery): JsonResponse
        {
            $this->authorize('track', $delivery);

            return new \Illuminate\Http\JsonResponse([
                'success' => true,
                'status' => $delivery->status,
                'location' => $delivery->delivery_point,
                'eta' => $delivery->eta_minutes,
            ]);
        }

        public function cancel(Request $request, DeliveryOrder $delivery): JsonResponse
        {
            $this->authorize('cancel', $delivery);

            $correlationId = Str::uuid()->toString();
            $reason = $request->input('reason', 'Customer request');

            try {
                $before = $delivery->status;

                $delivery->update([
                    'status' => 'cancelled',
                    'cancelled_at' => Carbon::now(),
                    'cancellation_reason' => $reason,
                ]);

                $this->logger->info('Delivery cancelled', [
                    'correlation_id' => $correlationId,
                    'delivery_id' => $delivery->id,
                    'tenant_id' => $delivery->tenant_id ?? null,
                    'before' => $before,
                    'after' => 'cancelled',
                    'reason' => $reason,
                    'user_id' => $request->user()?->id,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'message' => 'Доставка отменена',
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Delivery cancellation failed', [
                    'correlation_id' => $correlationId,
                    'delivery_id' => $delivery->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'correlation_id' => $correlationId], 500);
            }
        }
}
