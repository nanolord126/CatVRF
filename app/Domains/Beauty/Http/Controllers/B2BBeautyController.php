<?php
declare(strict_types=1);

namespace App\Domains\Beauty\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final class B2BBeautyController extends Controller
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    public function storefronts(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $storefronts = $this->db->table('b2b_beauty_storefronts')
            ->where('tenant_id', $request->get('tenant_id'))
            ->orderByDesc('created_at')
            ->paginate(20);

        $this->logger->info('B2B storefronts listed', ['correlation_id' => $correlationId, 'count' => $storefronts->total()]);

        return new JsonResponse([
            'correlation_id' => $correlationId,
            'data' => $storefronts->items(),
            'meta' => ['current_page' => $storefronts->currentPage(), 'last_page' => $storefronts->lastPage(), 'total' => $storefronts->total()],
        ]);
    }

    public function createStorefront(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'inn' => 'required|string|max:12',
            'address' => 'required|string',
            'contact_phone' => 'nullable|string',
        ]);

        $id = $this->db->transaction(function () use ($validated, $request, $correlationId) {
            return $this->db->table('b2b_beauty_storefronts')->insertGetId(array_merge($validated, [
                'tenant_id' => $request->get('tenant_id'),
                'uuid' => (string) Str::uuid(),
                'correlation_id' => $correlationId,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        });

        $this->logger->info('B2B storefront created', ['correlation_id' => $correlationId, 'id' => $id]);

        return new JsonResponse(['correlation_id' => $correlationId, 'id' => $id, 'message' => 'B2B-витрина создана'], 201);
    }

    public function createOrder(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $validated = $request->validate([
            'storefront_id' => 'required|integer',
            'items' => 'required|array|min:1',
            'total' => 'required|numeric|min:0',
        ]);

        $id = $this->db->transaction(function () use ($validated, $request, $correlationId) {
            return $this->db->table('b2b_beauty_orders')->insertGetId([
                'tenant_id' => $request->get('tenant_id'),
                'storefront_id' => $validated['storefront_id'],
                'items' => json_encode($validated['items']),
                'total' => $validated['total'],
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

    public function myB2BOrders(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $orders = $this->db->table('b2b_beauty_orders')
            ->where('tenant_id', $request->get('tenant_id'))
            ->orderByDesc('created_at')
            ->paginate(20);

        return new JsonResponse([
            'correlation_id' => $correlationId,
            'data' => $orders->items(),
            'meta' => ['current_page' => $orders->currentPage(), 'last_page' => $orders->lastPage(), 'total' => $orders->total()],
        ]);
    }

    public function approveOrder(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $this->db->transaction(function () use ($id, $request) {
            $this->db->table('b2b_beauty_orders')
                ->where('id', $id)
                ->where('tenant_id', $request->get('tenant_id'))
                ->update(['status' => 'approved', 'updated_at' => now()]);
        });

        $this->logger->info('B2B order approved', ['correlation_id' => $correlationId, 'id' => $id]);

        return new JsonResponse(['correlation_id' => $correlationId, 'message' => 'Заказ одобрен']);
    }

    public function rejectOrder(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $this->db->transaction(function () use ($id, $request) {
            $this->db->table('b2b_beauty_orders')
                ->where('id', $id)
                ->where('tenant_id', $request->get('tenant_id'))
                ->update(['status' => 'rejected', 'updated_at' => now()]);
        });

        $this->logger->info('B2B order rejected', ['correlation_id' => $correlationId, 'id' => $id]);

        return new JsonResponse(['correlation_id' => $correlationId, 'message' => 'Заказ отклонён']);
    }

    public function verifyInn(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $this->db->transaction(function () use ($id) {
            $this->db->table('b2b_beauty_storefronts')
                ->where('id', $id)
                ->update(['inn_verified' => true, 'status' => 'active', 'updated_at' => now()]);
        });

        $this->logger->info('B2B INN verified', ['correlation_id' => $correlationId, 'storefront_id' => $id]);

        return new JsonResponse(['correlation_id' => $correlationId, 'message' => 'ИНН верифицирован']);
    }
}
