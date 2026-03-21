<?php declare(strict_types=1);

namespace App\Domains\Food\Http\Controllers;

use App\Domains\Food\Models\DeliveryOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Controller для управления доставками.
 * Production 2026.
 */
final class DeliveryOrderController
{
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
        try {
            $delivery->update([
                'status' => 'on_way',
                'picked_up_at' => now(),
            ]);

            event(new \App\Domains\Food\Events\DeliveryStarted($delivery, ''));

            return response()->json(['success' => true, 'message' => 'Доставка начата']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false], 500);
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
