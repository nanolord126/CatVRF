<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final class B2BOrderController extends Controller
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $orders = $this->db->table('b2b_orders')
            ->where('business_group_id', $request->get('business_group_id'))
            ->orderByDesc('created_at')
            ->paginate(20);

        $this->logger->info('B2B orders listed', ['correlation_id' => $correlationId, 'count' => $orders->total()]);

        return new JsonResponse([
            'correlation_id' => $correlationId,
            'data' => $orders->items(),
            'meta' => ['current_page' => $orders->currentPage(), 'last_page' => $orders->lastPage(), 'total' => $orders->total()],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
            'delivery_address' => 'required|string',
            'payment_terms' => 'nullable|string|in:prepaid,net_7,net_14,net_30',
        ]);

        $id = $this->db->transaction(function () use ($validated, $request, $correlationId) {
            return $this->db->table('b2b_orders')->insertGetId([
                'business_group_id' => $request->get('business_group_id'),
                'tenant_id' => $request->get('tenant_id'),
                'items' => json_encode($validated['items']),
                'delivery_address' => $validated['delivery_address'],
                'payment_terms' => $validated['payment_terms'] ?? 'prepaid',
                'uuid' => (string) Str::uuid(),
                'correlation_id' => $correlationId,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $this->logger->info('B2B order created', ['correlation_id' => $correlationId, 'id' => $id]);

        return new JsonResponse(['correlation_id' => $correlationId, 'id' => $id, 'message' => 'B2B-заказ создан'], 201);
    }
}
