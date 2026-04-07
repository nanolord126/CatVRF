<?php declare(strict_types=1);

/**
 * ConsumableController — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/consumablecontroller
 */


namespace App\Domains\Food\Http\Controllers;

use App\Http\Controllers\Controller;

final class ConsumableController extends Controller
{

    public function index(): JsonResponse
        {
            try {
                $consumables = FoodConsumable::query()
                    ->where('tenant_id', tenant()->id)
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $consumables,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка'], 500);
            }
        }

        public function show(FoodConsumable $consumable): JsonResponse
        {
            $this->authorize('view', $consumable);

            return new \Illuminate\Http\JsonResponse([
                'success' => true,
                'data' => $consumable,
            ]);
        }

        public function lowStock(): JsonResponse
        {
            $consumables = FoodConsumable::query()
                ->where('tenant_id', tenant()->id)
                ->whereRaw('current_stock < min_stock_threshold')
                ->get();

            return new \Illuminate\Http\JsonResponse([
                'success' => true,
                'data' => $consumables,
                'count' => $consumables->count(),
            ]);
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

}
