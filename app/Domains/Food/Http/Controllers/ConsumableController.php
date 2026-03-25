declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Food\Http\Controllers;

use App\Domains\Food\Models\FoodConsumable;
use Illuminate\Http\JsonResponse;

/**
 * Controller для управления ингредиентами/расходниками.
 * Production 2026.
 */
final class ConsumableController
{
    public function index(): JsonResponse
    {
        try {
            $consumables = FoodConsumable::query()
                ->where('tenant_id', tenant('id'))
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $consumables,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Ошибка'], 500);
        }
    }

    public function show(FoodConsumable $consumable): JsonResponse
    {
        $this->authorize('view', $consumable);

        return response()->json([
            'success' => true,
            'data' => $consumable,
        ]);
    }

    public function lowStock(): JsonResponse
    {
        $consumables = FoodConsumable::query()
            ->where('tenant_id', tenant('id'))
            ->whereRaw('current_stock < min_stock_threshold')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $consumables,
            'count' => $consumables->count(),
        ]);
    }
}
