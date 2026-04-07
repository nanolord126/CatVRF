<?php declare(strict_types=1);

namespace App\Domains\Food\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class RestaurantController extends Controller
{
    public function __construct(
        private readonly LoggerInterface $logger) {}


    public function index(): JsonResponse
        {
            try {
                $correlationId = Str::uuid()->toString();

                $restaurants = Restaurant::query()
                    ->where('tenant_id', tenant()->id)
                    ->select(['id', 'name', 'address', 'geo_point', 'rating', 'cuisine_type', 'is_verified'])
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $restaurants,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to fetch restaurants', ['error' => $e->getMessage()]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка'], 500);
            }
        }

        public function show(Restaurant $restaurant): JsonResponse
        {
            try {
                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $restaurant->load(['menus', 'tables']),
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ресторан не найден'], 404);
            }
        }

        public function getMenu(Restaurant $restaurant): JsonResponse
        {
            try {
                $menus = $restaurant->menus()
                    ->where('is_active', true)
                    ->with(['dishes' => fn ($q) => $q->where('is_available', true)])
                    ->get();

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $menus,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка'], 500);
            }
        }
}
