<?php
declare(strict_types=1);

namespace App\Domains\Education\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final class EducationController extends Controller
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
        $tenantId = $request->get('tenant_id');

        $items = $this->db->table('education_courses')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->paginate(20);

        $this->logger->info('Курс listed', [
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
            'title' => 'required|string|max:255', 'category' => 'required|string', 'price' => 'required|numeric|min:0', 'duration_hours' => 'nullable|integer|min:1', 'instructor' => 'nullable|string',
        ]);

        $id = $this->db->transaction(function () use ($validated, $request, $correlationId) {
            $data = array_merge($validated, [
                'tenant_id' => $request->get('tenant_id'),
                'uuid' => (string) Str::uuid(),
                'correlation_id' => $correlationId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $this->db->table('education_courses')->insertGetId($data);
        });

        $this->logger->info('Курс created', ['correlation_id' => $correlationId, 'id' => $id]);

        return new JsonResponse(['correlation_id' => $correlationId, 'id' => $id, 'message' => 'Курс создан(а)'], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $item = $this->db->table('education_courses')
            ->where('id', $id)
            ->where('tenant_id', $request->get('tenant_id'))
            ->first();

        if ($item === null) {
            return new JsonResponse(['correlation_id' => $correlationId, 'message' => 'Курс не найден(а)'], 404);
        }

        return new JsonResponse(['correlation_id' => $correlationId, 'data' => $item]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255', 'category' => 'sometimes|string', 'price' => 'sometimes|numeric|min:0', 'duration_hours' => 'sometimes|integer|min:1', 'instructor' => 'sometimes|string',
        ]);

        $this->db->transaction(function () use ($validated, $id, $request) {
            $data = array_merge($validated, ['updated_at' => now()]);

            $this->db->table('education_courses')
                ->where('id', $id)
                ->where('tenant_id', $request->get('tenant_id'))
                ->update($data);
        });

        $this->logger->info('Курс updated', ['correlation_id' => $correlationId, 'id' => $id]);

        return new JsonResponse(['correlation_id' => $correlationId, 'message' => 'Курс обновлён(а)']);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $this->db->transaction(function () use ($id, $request) {
            $this->db->table('education_courses')
                ->where('id', $id)
                ->where('tenant_id', $request->get('tenant_id'))
                ->delete();
        });

        $this->logger->info('Курс deleted', ['correlation_id' => $correlationId, 'id' => $id]);

        return new JsonResponse(['correlation_id' => $correlationId, 'message' => 'Курс удалён(а)']);
    }
}
