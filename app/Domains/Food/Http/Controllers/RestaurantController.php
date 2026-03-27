<?php declare(strict_types=1);

namespace App\Domains\Food\Http\Controllers;

use App\Domains\Food\Models\Restaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Controller для управления ресторанами.
 * Production 2026.
 */
final class RestaurantController
{
    public function index(): JsonResponse
    {
        try {
            $correlationId = Str::uuid()->toString();

            $restaurants = Restaurant::query()
                ->where('tenant_id', tenant('id'))
                ->select(['id', 'name', 'address', 'geo_point', 'rating', 'cuisine_type', 'is_verified'])
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $restaurants,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to fetch restaurants', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Ошибка'], 500);
        }
    }

    public function show(Restaurant $restaurant): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $restaurant->load(['menus', 'tables']),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Ресторан не найден'], 404);
        }
    }

    public function getMenu(Restaurant $restaurant): JsonResponse
    {
        try {
            $menus = $restaurant->menus()
                ->where('is_active', true)
                ->with(['dishes' => fn ($q) => $q->where('is_available', true)])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $menus,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Ошибка'], 500);
        }
    }
}
