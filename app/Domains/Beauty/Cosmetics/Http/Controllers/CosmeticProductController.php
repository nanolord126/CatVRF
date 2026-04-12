<?php
declare(strict_types=1);

namespace App\Domains\Beauty\Cosmetics\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final class CosmeticProductController extends Controller
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $products = $this->db->table('cosmetic_products')
            ->where('tenant_id', $request->get('tenant_id'))
            ->where('is_active', true)
            ->orderByDesc('created_at')
            ->paginate(20);

        $this->logger->info('Cosmetics listed', ['correlation_id' => $correlationId, 'count' => $products->total()]);

        return new JsonResponse([
            'correlation_id' => $correlationId,
            'data' => $products->items(),
            'meta' => ['current_page' => $products->currentPage(), 'last_page' => $products->lastPage(), 'total' => $products->total()],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $product = $this->db->table('cosmetic_products')->where('id', $id)->first();

        if ($product === null) {
            return new JsonResponse(['correlation_id' => $correlationId, 'message' => 'Товар не найден'], 404);
        }

        return new JsonResponse(['correlation_id' => $correlationId, 'data' => $product]);
    }

    public function tryOn(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $product = $this->db->table('cosmetic_products')->where('id', $id)->first();

        if ($product === null) {
            return new JsonResponse(['correlation_id' => $correlationId, 'message' => 'Товар не найден'], 404);
        }

        $this->logger->info('Cosmetic AR try-on requested', ['correlation_id' => $correlationId, 'product_id' => $id]);

        return new JsonResponse([
            'correlation_id' => $correlationId,
            'ar_url' => '/cosmetics/ar-preview/' . $id,
            'product_id' => $id,
        ]);
    }

    public function order(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'delivery_address' => 'required|string',
        ]);

        $id = $this->db->transaction(function () use ($validated, $request, $correlationId) {
            return $this->db->table('cosmetic_orders')->insertGetId([
                'tenant_id' => $request->get('tenant_id'),
                'user_id' => $request->user()?->id,
                'items' => json_encode($validated['items']),
                'delivery_address' => $validated['delivery_address'],
                'uuid' => (string) Str::uuid(),
                'correlation_id' => $correlationId,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $this->logger->info('Cosmetic order created', ['correlation_id' => $correlationId, 'id' => $id]);

        return new JsonResponse(['correlation_id' => $correlationId, 'id' => $id, 'message' => 'Заказ создан'], 201);
    }

    public function myOrders(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $orders = $this->db->table('cosmetic_orders')
            ->where('user_id', $request->user()?->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return new JsonResponse([
            'correlation_id' => $correlationId,
            'data' => $orders->items(),
            'meta' => ['current_page' => $orders->currentPage(), 'last_page' => $orders->lastPage(), 'total' => $orders->total()],
        ]);
    }
}
