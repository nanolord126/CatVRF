<?php
declare(strict_types=1);

namespace App\Domains\ToysAndGames\ToysAndGames\ToysKids\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final class ToyProductController extends Controller
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $toys = $this->db->table('toy_products')
            ->where('tenant_id', $request->get('tenant_id'))
            ->where('is_active', true)
            ->orderByDesc('created_at')
            ->paginate(20);

        $this->logger->info('Toys listed', ['correlation_id' => $correlationId, 'count' => $toys->total()]);

        return new JsonResponse([
            'correlation_id' => $correlationId,
            'data' => $toys->items(),
            'meta' => ['current_page' => $toys->currentPage(), 'last_page' => $toys->lastPage(), 'total' => $toys->total()],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $toy = $this->db->table('toy_products')->where('id', $id)->first();

        if ($toy === null) {
            return new JsonResponse(['correlation_id' => $correlationId, 'message' => 'Товар не найден'], 404);
        }

        return new JsonResponse(['correlation_id' => $correlationId, 'data' => $toy]);
    }

    public function wishlist(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $validated = $request->validate([
            'product_id' => 'required|integer',
        ]);

        $this->db->table('toy_wishlists')->updateOrInsert(
            ['user_id' => $request->user()?->id, 'product_id' => $validated['product_id']],
            ['created_at' => now(), 'updated_at' => now()]
        );

        $this->logger->info('Toy added to wishlist', ['correlation_id' => $correlationId, 'product_id' => $validated['product_id']]);

        return new JsonResponse(['correlation_id' => $correlationId, 'message' => 'Добавлено в избранное']);
    }

    public function order(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'delivery_address' => 'required|string',
        ]);

        $id = $this->db->transaction(function () use ($validated, $request, $correlationId) {
            return $this->db->table('toy_orders')->insertGetId([
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

        $this->logger->info('Toy order created', ['correlation_id' => $correlationId, 'id' => $id]);

        return new JsonResponse(['correlation_id' => $correlationId, 'id' => $id, 'message' => 'Заказ создан'], 201);
    }

    public function myOrders(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $orders = $this->db->table('toy_orders')
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
