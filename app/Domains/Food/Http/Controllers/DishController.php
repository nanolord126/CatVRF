<?php declare(strict_types=1);

namespace App\Domains\Food\Http\Controllers;

use App\Domains\Food\Models\Dish;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Controller для управления блюдами.
 * Production 2026.
 */
final class DishController
{
    public function index(): JsonResponse
    {
        try {
            $dishes = Dish::query()
                ->where('tenant_id', tenant('id') ?? 1)
                ->where('is_available', true)
                ->select(['id', 'name', 'description', 'price', 'calories', 'allergens', 'image_url', 'rating'])
                ->paginate(30);

            return response()->json([
                'success' => true,
                'data' => $dishes,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Ошибка'], 500);
        }
    }

    public function show(Dish $dish): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $dish,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Блюдо не найдено'], 404);
        }
    }

    public function store(): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        return response()->json(['success' => false], 501);
    }

    public function update(): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        return response()->json(['success' => false], 501);
    }

    public function destroy(): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        return response()->json(['success' => false], 501);
    }
}
