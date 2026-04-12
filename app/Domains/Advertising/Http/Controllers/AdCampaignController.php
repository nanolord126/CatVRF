<?php
declare(strict_types=1);

namespace App\Domains\Advertising\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final class AdCampaignController extends Controller
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
        $tenantId = $request->get('tenant_id');

        $campaigns = $this->db->table('ad_campaigns')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->paginate(20);

        $this->logger->info('Ad campaigns listed', [
            'correlation_id' => $correlationId,
            'tenant_id' => $tenantId,
            'count' => $campaigns->total(),
        ]);

        return new JsonResponse([
            'correlation_id' => $correlationId,
            'data' => $campaigns->items(),
            'meta' => [
                'current_page' => $campaigns->currentPage(),
                'last_page' => $campaigns->lastPage(),
                'total' => $campaigns->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'budget' => 'required|numeric|min:0',
            'type' => 'required|string|in:email,push,shorts,banner',
            'targeting' => 'nullable|array',
        ]);

        $campaign = $this->db->transaction(function () use ($validated, $request, $correlationId) {
            return $this->db->table('ad_campaigns')->insertGetId([
                'tenant_id' => $request->get('tenant_id'),
                'uuid' => (string) Str::uuid(),
                'correlation_id' => $correlationId,
                'name' => $validated['name'],
                'budget' => $validated['budget'],
                'type' => $validated['type'],
                'targeting' => json_encode($validated['targeting'] ?? []),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $this->logger->info('Ad campaign created', [
            'correlation_id' => $correlationId,
            'campaign_id' => $campaign,
        ]);

        return new JsonResponse([
            'correlation_id' => $correlationId,
            'id' => $campaign,
            'message' => 'Рекламная кампания создана',
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $campaign = $this->db->table('ad_campaigns')
            ->where('id', $id)
            ->where('tenant_id', $request->get('tenant_id'))
            ->first();

        if ($campaign === null) {
            return new JsonResponse([
                'correlation_id' => $correlationId,
                'message' => 'Кампания не найдена',
            ], 404);
        }

        return new JsonResponse([
            'correlation_id' => $correlationId,
            'data' => $campaign,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'budget' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|string|in:active,paused,completed',
            'targeting' => 'sometimes|array',
        ]);

        $updated = $this->db->transaction(function () use ($validated, $id, $request, $correlationId) {
            $data = array_merge($validated, ['updated_at' => now()]);
            if (isset($data['targeting'])) {
                $data['targeting'] = json_encode($data['targeting']);
            }

            return $this->db->table('ad_campaigns')
                ->where('id', $id)
                ->where('tenant_id', $request->get('tenant_id'))
                ->update($data);
        });

        $this->logger->info('Ad campaign updated', [
            'correlation_id' => $correlationId,
            'campaign_id' => $id,
            'updated' => $updated,
        ]);

        return new JsonResponse([
            'correlation_id' => $correlationId,
            'message' => 'Кампания обновлена',
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $this->db->transaction(function () use ($id, $request) {
            $this->db->table('ad_campaigns')
                ->where('id', $id)
                ->where('tenant_id', $request->get('tenant_id'))
                ->delete();
        });

        $this->logger->info('Ad campaign deleted', [
            'correlation_id' => $correlationId,
            'campaign_id' => $id,
        ]);

        return new JsonResponse([
            'correlation_id' => $correlationId,
            'message' => 'Кампания удалена',
        ]);
    }
}
