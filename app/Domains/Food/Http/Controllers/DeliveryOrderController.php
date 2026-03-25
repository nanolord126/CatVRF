<?php declare(strict_types=1);

namespace App\Domains\Food\Http\Controllers;

use App\Domains\Food\Models\DeliveryOrder;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Controller для управления доставками.
 * Production 2026.
 */
final class DeliveryOrderController
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function index(): JsonResponse
    {
        try {
            $deliveries = DeliveryOrder::query()
                ->whereHas('order', fn ($q) => $q->where('client_id', auth()->id()))
                ->with('order')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $deliveries,
            ]);
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('DeliveryOrder index failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['success' => false, 'message' => 'Ошибка'], 500);
        }
    }

    public function show(DeliveryOrder $delivery): JsonResponse
    {
        $this->authorize('view', $delivery);

        return response()->json([
            'success' => true,
            'data' => $delivery->load('order'),
        ]);
    }

    public function start(DeliveryOrder $delivery): JsonResponse
    {
        $correlationId = Str::uuid()->toString();

        $fraudResult = $this->fraudControlService->check(
            userId: auth()->id() ?? 0,
            operationType: 'delivery_start',
            amount: 0,
            ipAddress: request()->ip(),
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        if ($fraudResult['decision'] === 'block') {
            $this->log->channel('fraud_alert')->warning('Delivery start blocked by fraud control', [
                'correlation_id' => $correlationId,
                'delivery_id' => $delivery->id,
                'score' => $fraudResult['score'],
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Операция заблокирована.',
                'correlation_id' => $correlationId,
            ], 403);
        }

        try {
            $before = $delivery->status;

            $delivery->update([
                'status' => 'on_way',
                'picked_up_at' => now(),
            ]);

            $this->log->channel('audit')->info('Delivery started', [
                'correlation_id' => $correlationId,
                'delivery_id' => $delivery->id,
                'tenant_id' => $delivery->tenant_id ?? null,
                'before' => $before,
                'after' => 'on_way',
                'user_id' => auth()->id(),
            ]);

            event(new \App\Domains\Food\Events\DeliveryStarted($delivery, $correlationId));

            return response()->json([
                'success' => true,
                'message' => 'Доставка начата',
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Delivery start failed', [
                'correlation_id' => $correlationId,
                'delivery_id' => $delivery->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['success' => false, 'correlation_id' => $correlationId], 500);
        }
    }

    public function track(DeliveryOrder $delivery): JsonResponse
    {
        $this->authorize('track', $delivery);

        return response()->json([
            'success' => true,
            'status' => $delivery->status,
            'location' => $delivery->delivery_point,
            'eta' => $delivery->eta_minutes,
        ]);
    }
}
