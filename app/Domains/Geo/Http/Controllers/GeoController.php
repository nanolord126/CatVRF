<?php
declare(strict_types=1);

namespace App\Domains\Geo\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final class GeoController extends Controller
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
        $tenantId = $request->get('tenant_id');

        $items = $this->db->table('geo_zones')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->paginate(20);

        $this->logger->info('Гео-зона listed', [
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
            'name' => 'required|string|max:255', 'type' => 'required|string|in:delivery,service,warehouse', 'polygon' => 'required|array', 'is_active' => 'nullable|boolean',
        ]);

        $id = $this->db->transaction(function () use ($validated, $request, $correlationId) {
            $data = array_merge($validated, [
                'tenant_id' => $request->get('tenant_id'),
                'uuid' => (string) Str::uuid(),
                'correlation_id' => $correlationId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            if (isset($data['polygon'])) { $data['polygon'] = json_encode($data['polygon']); }

            return $this->db->table('geo_zones')->insertGetId($data);
        });

        $this->logger->info('Гео-зона created', ['correlation_id' => $correlationId, 'id' => $id]);

        return new JsonResponse(['correlation_id' => $correlationId, 'id' => $id, 'message' => 'Гео-зона создан(а)'], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $item = $this->db->table('geo_zones')
            ->where('id', $id)
            ->where('tenant_id', $request->get('tenant_id'))
            ->first();

        if ($item === null) {
            return new JsonResponse(['correlation_id' => $correlationId, 'message' => 'Гео-зона не найден(а)'], 404);
        }

        return new JsonResponse(['correlation_id' => $correlationId, 'data' => $item]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255', 'type' => 'sometimes|string', 'polygon' => 'sometimes|array', 'is_active' => 'sometimes|boolean',
        ]);

        $this->db->transaction(function () use ($validated, $id, $request) {
            $data = array_merge($validated, ['updated_at' => now()]);
            if (isset($data['polygon'])) { $data['polygon'] = json_encode($data['polygon']); }

            $this->db->table('geo_zones')
                ->where('id', $id)
                ->where('tenant_id', $request->get('tenant_id'))
                ->update($data);
        });

        $this->logger->info('Гео-зона updated', ['correlation_id' => $correlationId, 'id' => $id]);

        return new JsonResponse(['correlation_id' => $correlationId, 'message' => 'Гео-зона обновлён(а)']);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $this->db->transaction(function () use ($id, $request) {
            $this->db->table('geo_zones')
                ->where('id', $id)
                ->where('tenant_id', $request->get('tenant_id'))
                ->delete();
        });

        $this->logger->info('Гео-зона deleted', ['correlation_id' => $correlationId, 'id' => $id]);

        return new JsonResponse(['correlation_id' => $correlationId, 'message' => 'Гео-зона удалён(а)']);
    }
}
