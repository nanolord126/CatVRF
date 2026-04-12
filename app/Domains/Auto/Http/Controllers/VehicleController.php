<?php
declare(strict_types=1);

namespace App\Domains\Auto\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final class VehicleController extends Controller
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
        $tenantId = $request->get('tenant_id');

        $items = $this->db->table('vehicles')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->paginate(20);

        $this->logger->info('Vehicles listed', [
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
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'year' => 'required|integer|min:1990|max:2030',
            'vin' => 'nullable|string|max:17',
            'price' => 'required|numeric|min:0',
            'mileage' => 'nullable|integer|min:0',
        ]);

        $id = $this->db->transaction(function () use ($validated, $request, $correlationId) {
            return $this->db->table('vehicles')->insertGetId(array_merge($validated, [
                'tenant_id' => $request->get('tenant_id'),
                'uuid' => (string) Str::uuid(),
                'correlation_id' => $correlationId,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        });

        $this->logger->info('Vehicle created', ['correlation_id' => $correlationId, 'id' => $id]);

        return new JsonResponse(['correlation_id' => $correlationId, 'id' => $id, 'message' => 'Транспорт создан'], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $item = $this->db->table('vehicles')
            ->where('id', $id)
            ->where('tenant_id', $request->get('tenant_id'))
            ->first();

        if ($item === null) {
            return new JsonResponse(['correlation_id' => $correlationId, 'message' => 'Не найдено'], 404);
        }

        return new JsonResponse(['correlation_id' => $correlationId, 'data' => $item]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $validated = $request->validate([
            'brand' => 'sometimes|string|max:255',
            'model' => 'sometimes|string|max:255',
            'year' => 'sometimes|integer',
            'vin' => 'sometimes|string|max:17',
            'price' => 'sometimes|numeric|min:0',
            'mileage' => 'sometimes|integer|min:0',
        ]);

        $this->db->transaction(function () use ($validated, $id, $request) {
            $this->db->table('vehicles')
                ->where('id', $id)
                ->where('tenant_id', $request->get('tenant_id'))
                ->update(array_merge($validated, ['updated_at' => now()]));
        });

        $this->logger->info('Vehicle updated', ['correlation_id' => $correlationId, 'id' => $id]);

        return new JsonResponse(['correlation_id' => $correlationId, 'message' => 'Обновлено']);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $this->db->transaction(function () use ($id, $request) {
            $this->db->table('vehicles')
                ->where('id', $id)
                ->where('tenant_id', $request->get('tenant_id'))
                ->delete();
        });

        $this->logger->info('Vehicle deleted', ['correlation_id' => $correlationId, 'id' => $id]);

        return new JsonResponse(['correlation_id' => $correlationId, 'message' => 'Удалено']);
    }
}
