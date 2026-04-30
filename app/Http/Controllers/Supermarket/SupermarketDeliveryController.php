<?php

declare(strict_types=1);

namespace App\Http\Controllers\Supermarket;

use App\Domains\Shared\Geo\GeoLogisticsAdapter;
use App\Domains\Supermarket\Services\SupermarketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

final readonly class SupermarketDeliveryController
{
    public function __construct(
        private readonly GeoLogisticsAdapter $geoAdapter,
        private readonly SupermarketService $supermarketService,
    ) {}

    /**
     * Проверить доступность доставки по адресу.
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'address' => 'required|string',
            'sub_vertical' => 'nullable|string',
        ]);

        try {
            $isAvailable = $this->supermarketService->checkDeliveryAvailability($validated['address']);

            return response()->json([
                'success' => true,
                'data' => [
                    'address' => $validated['address'],
                    'delivery_available' => $isAvailable,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить доступные слоты доставки (кэшированные).
     */
    public function getAvailableSlots(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'address' => 'required|string',
            'sub_vertical' => 'nullable|string',
        ]);

        $address = $validated['address'];
        $subVertical = $validated['sub_vertical'] ?? 'grocery_and_delivery';

        // Кэш ключ на основе адреса и под-вертикали
        $cacheKey = "supermarket:delivery_slots:" . md5($address . $subVertical);

        $slots = Cache::tags(['supermarket', 'delivery_slots', $subVertical])
            ->remember($cacheKey, now()->addMinutes(15), function () use ($address, $subVertical) {
                return $this->geoAdapter->getAvailableSlots(
                    address: $address,
                    vertical: 'supermarket',
                    subVertical: $subVertical,
                );
            });

        return response()->json([
            'success' => true,
            'data' => [
                'address' => $address,
                'sub_vertical' => $subVertical,
                'slots' => $slots,
                'cached_at' => now(),
            ],
        ]);
    }

    /**
     * Рассчитать стоимость доставки.
     */
    public function calculateDelivery(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'seller_address' => 'required|string',
            'buyer_address' => 'required|string',
            'items' => 'nullable|array',
            'items.*.weight' => 'nullable|numeric',
            'items.*.volume' => 'nullable|numeric',
            'sub_vertical' => 'nullable|string',
            'cold_chain' => 'nullable|boolean',
        ]);

        try {
            $calculation = $this->geoAdapter->calculateDeliveryForOrder([
                'vertical' => 'supermarket',
                'sub_vertical' => $validated['sub_vertical'] ?? null,
                'seller_address' => $validated['seller_address'],
                'buyer_address' => $validated['buyer_address'],
                'items' => $validated['items'] ?? [],
                'cold_chain' => $validated['cold_chain'] ?? false,
            ]);

            return response()->json([
                'success' => true,
                'data' => $calculation,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
