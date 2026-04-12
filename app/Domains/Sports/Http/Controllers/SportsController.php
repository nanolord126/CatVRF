<?php
declare(strict_types=1);

namespace App\Domains\Sports\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final class SportsController extends Controller
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
        $tenantId = $request->get('tenant_id');

        $items = $this->db->table('sports_facilities')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->paginate(20);

        $this->logger->info('Спортивный объект listed', [
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
            'name' => 'required|string|max:255', 'type' => 'required|string', 'address' => 'required|string', 'capacity' => 'nullable|integer|min:1', 'price_hourly' => 'nullable|numeric|min:0',
        ]);

        $id = $this->db->transaction(function () use ($validated, $request, $correlationId) {
            $data = array_merge($validated, [
                'tenant_id' => $request->get('tenant_id'),
                'uuid' => (string) Str::uuid(),
                'correlation_id' => $correlationId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $this->db->table('sports_facilities')->insertGetId($data);
        });

        $this->logger->info('Спортивный объект created', ['correlation_id' => $correlationId, 'id' => $id]);

        return new JsonResponse(['correlation_id' => $correlationId, 'id' => $id, 'message' => 'Спортивный объект создан(а)'], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $item = $this->db->table('sports_facilities')
            ->where('id', $id)
            ->where('tenant_id', $request->get('tenant_id'))
            ->first();

        if ($item === null) {
            return new JsonResponse(['correlation_id' => $correlationId, 'message' => 'Спортивный объект не найден(а)'], 404);
        }

        return new JsonResponse(['correlation_id' => $correlationId, 'data' => $item]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255', 'type' => 'sometimes|string', 'address' => 'sometimes|string', 'capacity' => 'sometimes|integer|min:1', 'price_hourly' => 'sometimes|numeric|min:0',
        ]);

        $this->db->transaction(function () use ($validated, $id, $request) {
            $data = array_merge($validated, ['updated_at' => now()]);

            $this->db->table('sports_facilities')
                ->where('id', $id)
                ->where('tenant_id', $request->get('tenant_id'))
                ->update($data);
        });

        $this->logger->info('Спортивный объект updated', ['correlation_id' => $correlationId, 'id' => $id]);

        return new JsonResponse(['correlation_id' => $correlationId, 'message' => 'Спортивный объект обновлён(а)']);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $this->db->transaction(function () use ($id, $request) {
            $this->db->table('sports_facilities')
                ->where('id', $id)
                ->where('tenant_id', $request->get('tenant_id'))
                ->delete();
        });

        $this->logger->info('Спортивный объект deleted', ['correlation_id' => $correlationId, 'id' => $id]);

        return new JsonResponse(['correlation_id' => $correlationId, 'message' => 'Спортивный объект удалён(а)']);
    }
}
