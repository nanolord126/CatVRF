<?php
declare(strict_types=1);

namespace App\Domains\Beauty\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final class BeautyController extends Controller
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
        $tenantId = $request->get('tenant_id');

        $items = $this->db->table('beauty_salons')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->paginate(20);

        $this->logger->info('Салон красоты listed', [
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
            'name' => 'required|string|max:255', 'address' => 'required|string', 'lat' => 'nullable|numeric', 'lon' => 'nullable|numeric', 'tags' => 'nullable|array',
        ]);

        $id = $this->db->transaction(function () use ($validated, $request, $correlationId) {
            $data = array_merge($validated, [
                'tenant_id' => $request->get('tenant_id'),
                'uuid' => (string) Str::uuid(),
                'correlation_id' => $correlationId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            if (isset($data['tags'])) { $data['tags'] = json_encode($data['tags']); }

            return $this->db->table('beauty_salons')->insertGetId($data);
        });

        $this->logger->info('Салон красоты created', ['correlation_id' => $correlationId, 'id' => $id]);

        return new JsonResponse(['correlation_id' => $correlationId, 'id' => $id, 'message' => 'Салон красоты создан(а)'], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $item = $this->db->table('beauty_salons')
            ->where('id', $id)
            ->where('tenant_id', $request->get('tenant_id'))
            ->first();

        if ($item === null) {
            return new JsonResponse(['correlation_id' => $correlationId, 'message' => 'Салон красоты не найден(а)'], 404);
        }

        return new JsonResponse(['correlation_id' => $correlationId, 'data' => $item]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255', 'address' => 'sometimes|string', 'lat' => 'sometimes|numeric', 'lon' => 'sometimes|numeric', 'tags' => 'sometimes|array',
        ]);

        $this->db->transaction(function () use ($validated, $id, $request) {
            $data = array_merge($validated, ['updated_at' => now()]);
            if (isset($data['tags'])) { $data['tags'] = json_encode($data['tags']); }

            $this->db->table('beauty_salons')
                ->where('id', $id)
                ->where('tenant_id', $request->get('tenant_id'))
                ->update($data);
        });

        $this->logger->info('Салон красоты updated', ['correlation_id' => $correlationId, 'id' => $id]);

        return new JsonResponse(['correlation_id' => $correlationId, 'message' => 'Салон красоты обновлён(а)']);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $this->db->transaction(function () use ($id, $request) {
            $this->db->table('beauty_salons')
                ->where('id', $id)
                ->where('tenant_id', $request->get('tenant_id'))
                ->delete();
        });

        $this->logger->info('Салон красоты deleted', ['correlation_id' => $correlationId, 'id' => $id]);

        return new JsonResponse(['correlation_id' => $correlationId, 'message' => 'Салон красоты удалён(а)']);
    }
}
