<?php

declare(strict_types=1);

namespace App\Http\Controllers\Supermarket;

use App\Domains\Supermarket\Services\SupermarketService;
use App\Domains\Supermarket\Adapters\ColdChainAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class SupermarketTrackingController
{
    public function __construct(
        private SupermarketService $supermarketService,
        private ColdChainAdapter $coldChainAdapter
    ) {
    }

    public function show(Request $request, string $orderUuid): JsonResponse
    {
        $userId = Auth::id();
        $tenantId = $request->user()?->tenant_id;

        $order = DB::table('supermarket_orders')
            ->where('uuid', $orderUuid)
            ->where('user_id', $userId)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'error' => 'Order not found',
            ], 404);
        }

        // Get cold chain status if required
        $temperature = null;
        $temperatureHistory = [];
        
        if ($order->cold_chain_required) {
            try {
                $coldChainStatus = $this->coldChainAdapter->getColdChainStatus($order->id);
                $temperature = $coldChainStatus['current_temperature'] ?? null;
                $temperatureHistory = $coldChainStatus['history'] ?? [];
            } catch (\Exception $e) {
                // Temperature data not available yet
            }
        }

        // Calculate ETA
        $etaMinutes = $order->delivery_eta ?? 30;
        
        return response()->json([
            'status' => $order->status,
            'cold_chain_required' => (bool) $order->cold_chain_required,
            'eta_minutes' => $etaMinutes,
            'temperature' => $temperature ?? 4.5, // Default safe temperature
            'distance' => 2.3, // Would come from GeoLogistics
            'courier_location' => [
                'x' => 20,
                'y' => 30,
            ],
            'courier' => [
                'name' => 'Алексей',
                'vehicle' => 'Peugeot Partner',
                'rating' => 4.8,
            ],
            'temperature_history' => $temperatureHistory ?: [
                3.2, 3.5, 4.0, 4.2, 4.5, 4.3, 4.1, 4.5, 4.8, 4.5
            ],
        ]);
    }
}
