<?php declare(strict_types=1);

namespace App\Domains\Food\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ConsumableController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
