<?php
declare(strict_types=1);

namespace App\Domains\ConstructionAndRepair\ConstructionAndRepair\ConstructionMaterials\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final class MaterialController extends Controller
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $materials = $this->db->table('construction_materials')
            ->where('tenant_id', $request->get('tenant_id'))
            ->orderByDesc('created_at')
            ->paginate(20);

        $this->logger->info('Materials listed', ['correlation_id' => $correlationId, 'count' => $materials->total()]);

        return new JsonResponse([
            'correlation_id' => $correlationId,
            'data' => $materials->items(),
            'meta' => ['current_page' => $materials->currentPage(), 'last_page' => $materials->lastPage(), 'total' => $materials->total()],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $material = $this->db->table('construction_materials')->where('id', $id)->first();

        if ($material === null) {
            return new JsonResponse(['correlation_id' => $correlationId, 'message' => 'Материал не найден'], 404);
        }

        return new JsonResponse(['correlation_id' => $correlationId, 'data' => $material]);
    }

    public function calculate(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $validated = $request->validate([
            'materials' => 'required|array|min:1',
            'materials.*.id' => 'required|integer',
            'materials.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $totalCost = 0;
        $breakdown = [];

        foreach ($validated['materials'] as $item) {
            $material = $this->db->table('construction_materials')->where('id', $item['id'])->first();
            if ($material !== null) {
                $cost = (float) $material->price * $item['quantity'];
                $totalCost += $cost;
                $breakdown[] = [
                    'material_id' => $item['id'],
                    'name' => $material->name ?? 'N/A',
                    'quantity' => $item['quantity'],
                    'unit_price' => (float) $material->price,
                    'total' => $cost,
                ];
            }
        }

        $this->logger->info('Material cost calculated', ['correlation_id' => $correlationId, 'total' => $totalCost]);

        return new JsonResponse([
            'correlation_id' => $correlationId,
            'total_cost' => $totalCost,
            'breakdown' => $breakdown,
        ]);
    }

    public function order(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $validated = $request->validate([
            'materials' => 'required|array|min:1',
            'delivery_address' => 'required|string',
        ]);

        $id = $this->db->transaction(function () use ($validated, $request, $correlationId) {
            return $this->db->table('construction_material_orders')->insertGetId([
                'tenant_id' => $request->get('tenant_id'),
                'user_id' => $request->user()?->id,
                'materials' => json_encode($validated['materials']),
                'delivery_address' => $validated['delivery_address'],
                'uuid' => (string) Str::uuid(),
                'correlation_id' => $correlationId,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $this->logger->info('Material order created', ['correlation_id' => $correlationId, 'id' => $id]);

        return new JsonResponse(['correlation_id' => $correlationId, 'id' => $id, 'message' => 'Заказ материалов создан'], 201);
    }

    public function myOrders(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $orders = $this->db->table('construction_material_orders')
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
