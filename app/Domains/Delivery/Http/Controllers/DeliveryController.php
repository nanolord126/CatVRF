<?php
declare(strict_types=1);

namespace App\Domains\Delivery\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final class DeliveryController extends Controller
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
        $tenantId = $request->get('tenant_id');

        $items = $this->db->table('delivery_orders')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->paginate(20);

        $this->logger->info('Доставка listed', [
            'correlation_id' => $correlationId,
            'tenant_id' => $tenantId,
            'count' => $items->total(),
        ]);

        return new JsonResponse([
            'correlation_id' => $correlationId,
            'data' => $items->items(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $validated = $request->validate([
            'order_id' => 'required|integer', 'pickup_location' => 'required|array', 'delivery_location' => 'required|array', 'delivery_fee' => 'required|numeric|min:0',
        ]);

        $id = $this->db->transaction(function () use ($validated, $request, $correlationId) {
            $data = array_merge($validated, [
                'tenant_id' => $request->get('tenant_id'),
                'uuid' => (string) Str::uuid(),
                'correlation_id' => $correlationId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            if (isset($data['pickup_location'])) { $data['pickup_location'] = json_encode($data['pickup_location']); }
            if (isset($data['delivery_location'])) { $data['delivery_location'] = json_encode($data['delivery_location']); }

            return $this->db->table('delivery_orders')->insertGetId($data);
        });

        $this->logger->info('Доставка created', ['correlation_id' => $correlationId, 'id' => $id]);

        return new JsonResponse(['correlation_id' => $correlationId, 'id' => $id, 'message' => 'Доставка создан(а)'], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $item = $this->db->table('delivery_orders')
            ->where('id', $id)
            ->where('tenant_id', $request->get('tenant_id'))
            ->first();

        if ($item === null) {
            return new JsonResponse(['correlation_id' => $correlationId, 'message' => 'Доставка не найден(а)'], 404);
        }

        return new JsonResponse(['correlation_id' => $correlationId, 'data' => $item]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $validated = $request->validate([
            'status' => 'sometimes|string|in:pending,assigned,picked_up,in_transit,delivered,failed,cancelled', 'delivery_fee' => 'sometimes|numeric|min:0',
        ]);

        $this->db->transaction(function () use ($validated, $id, $request) {
            $data = array_merge($validated, ['updated_at' => now()]);
            if (isset($data['pickup_location'])) { $data['pickup_location'] = json_encode($data['pickup_location']); }
            if (isset($data['delivery_location'])) { $data['delivery_location'] = json_encode($data['delivery_location']); }

            $this->db->table('delivery_orders')
                ->where('id', $id)
                ->where('tenant_id', $request->get('tenant_id'))
                ->update($data);
        });

        $this->logger->info('Доставка updated', ['correlation_id' => $correlationId, 'id' => $id]);

        return new JsonResponse(['correlation_id' => $correlationId, 'message' => 'Доставка обновлён(а)']);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $this->db->transaction(function () use ($id, $request) {
            $this->db->table('delivery_orders')
                ->where('id', $id)
                ->where('tenant_id', $request->get('tenant_id'))
                ->delete();
        });

        $this->logger->info('Доставка deleted', ['correlation_id' => $correlationId, 'id' => $id]);

        return new JsonResponse(['correlation_id' => $correlationId, 'message' => 'Доставка удалён(а)']);
    }
}
