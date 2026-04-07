<?php declare(strict_types=1);

namespace App\Http\Controllers\B2B;

use App\Http\Controllers\Controller;
use App\Models\BusinessGroup;
use App\Services\B2B\B2BApiKeyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Routing\ResponseFactory;

/**
 * B2BApiKeyController — управление API-ключами для B2B-клиентов.
 *
 * Эндпоинты:
 *   GET    /api-keys        — список ключей бизнес-группы
 *   POST   /api-keys        — создание нового ключа
 *   DELETE /api-keys/{id}   — отзыв ключа
 *   POST   /api-keys/{id}/rotate — ротация ключа
 */
final class B2BApiKeyController extends Controller
{
    public function __construct(
        private B2BApiKeyService $keyService,
        private readonly DatabaseManager $db,
        private readonly ResponseFactory $response,
    ) {}

    /**
     * GET /api/b2b/v1/api-keys
     */
    public function index(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        $businessGroup = $request->attributes->get('b2b_business_group');

        $keys = $this->db->table('b2b_api_keys')
            ->where('business_group_id', $businessGroup->id)
            ->select(['id', 'uuid', 'name', 'permissions', 'expires_at', 'last_used_at', 'is_active', 'created_at'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn(object $k): array => [
                'id'          => $k->id,
                'uuid'        => $k->uuid,
                'name'        => $k->name,
                'permissions' => json_decode($k->permissions, true),
                'expires_at'  => $k->expires_at,
                'last_used_at'=> $k->last_used_at,
                'is_active'   => (bool) $k->is_active,
                'created_at'  => $k->created_at,
            ]);

        return $this->response->json([
            'success'        => true,
            'data'           => $keys,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * POST /api/b2b/v1/api-keys
     */
    public function store(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        $businessGroup = $request->attributes->get('b2b_business_group');

        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'permissions' => 'nullable|array',
            'permissions.*' => [
                'string',
                Rule::in(['orders.read', 'orders.write', 'products.read', 'stock.read', 'reports', 'api-keys']),
            ],
            'expires_at'  => 'nullable|date|after:today',
        ]);

        $result = $this->keyService->create(
            $businessGroup,
            $data['name'],
            $data['permissions'] ?? [],
            $correlationId,
            isset($data['expires_at']) ? \Carbon\Carbon::parse($data['expires_at']) : null,
        );

        return $this->response->json([
            'success'        => true,
            'message'        => 'API-ключ создан. Сохраните key — он показывается только один раз.',
            'data'           => [
                'id'          => $result['model']->id,
                'name'        => $result['model']->name,
                'key'         => $result['key'],   // raw key — только здесь!
                'permissions' => $result['model']->permissions,
                'expires_at'  => $result['model']->expires_at,
            ],
            'correlation_id' => $correlationId,
        ], 201);
    }

    /**
     * DELETE /api/b2b/v1/api-keys/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        $businessGroup = $request->attributes->get('b2b_business_group');

        $apiKey = \App\Models\B2BApiKey::where('id', $id)
            ->where('business_group_id', $businessGroup->id)
            ->firstOrFail();

        $this->keyService->revoke($apiKey, $correlationId);

        return $this->response->json([
            'success'        => true,
            'message'        => 'API-ключ отозван.',
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * POST /api/b2b/v1/api-keys/{id}/rotate
     */
    public function rotate(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        $businessGroup = $request->attributes->get('b2b_business_group');

        $apiKey = \App\Models\B2BApiKey::where('id', $id)
            ->where('business_group_id', $businessGroup->id)
            ->firstOrFail();

        $result = $this->keyService->rotate($apiKey, $correlationId);

        return $this->response->json([
            'success'        => true,
            'message'        => 'API-ключ ротирован. Сохраните новый key — он показывается только один раз.',
            'data'           => [
                'id'          => $result['model']->id,
                'name'        => $result['model']->name,
                'key'         => $result['key'],
                'permissions' => $result['model']->permissions,
                'expires_at'  => $result['model']->expires_at,
            ],
            'correlation_id' => $correlationId,
        ]);
    }
}
