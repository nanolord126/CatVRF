<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Beauty;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use App\Services\FraudControlService;

/**
 * Beauty Consumable API Controller — расходные материалы салонов.
 */
class ConsumableController extends Controller
{
    public function __construct(
        private readonly FraudControlService $fraudService,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
        private readonly Guard $guard,
        private readonly ResponseFactory $response,
    ) {}

    /**
     * GET /consumables — список расходников.
     */
    public function index(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            $consumables = $this->db->table('beauty_consumables')
                ->where('tenant_id', (int) $request->header('X-Tenant-ID', '0'))
                ->orderBy('name')
                ->paginate((int) $request->input('per_page', 20));

            return $this->response->json([
                'success' => true,
                'correlation_id' => $correlationId,
                'data' => $consumables->items(),
                'meta' => [
                    'current_page' => $consumables->currentPage(),
                    'last_page' => $consumables->lastPage(),
                    'total' => $consumables->total(),
                ],
            ], 200);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Consumables list failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to retrieve consumables',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * GET /consumables/{id}.
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            $item = $this->db->table('beauty_consumables')->where('id', $id)->first();

            if ($item === null) {
                return $this->response->json([
                    'success' => false,
                    'message' => 'Consumable not found',
                    'correlation_id' => $correlationId,
                ], 404);
            }

            return $this->response->json([
                'success' => true,
                'correlation_id' => $correlationId,
                'data' => $item,
            ], 200);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Consumable show failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to retrieve consumable',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * POST /consumables — создание расходника.
     */
    public function store(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            return $this->db->transaction(function () use ($request, $correlationId): JsonResponse {
                $this->fraudService->scoreOperation([
                    'type' => 'consumable_create',
                    'user_id' => auth()->id(),
                    'ip_address' => $request->ip(),
                    'correlation_id' => $correlationId,
                ]);

                $itemId = $this->db->table('beauty_consumables')->insertGetId([
                    'tenant_id' => (int) $request->header('X-Tenant-ID', '0'),
                    'uuid' => Str::uuid()->toString(),
                    'correlation_id' => $correlationId,
                    'name' => $request->input('name'),
                    'unit' => $request->input('unit', 'pcs'),
                    'quantity' => $request->integer('quantity', 0),
                    'min_stock' => $request->integer('min_stock', 5),
                    'cost_price' => $request->integer('cost_price', 0),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->logger->channel('audit')->info('Consumable created', [
                    'correlation_id' => $correlationId,
                    'consumable_id' => $itemId,
                    'user_id' => auth()->id(),
                ]);

                return $this->response->json([
                    'success' => true,
                    'message' => 'Consumable created',
                    'correlation_id' => $correlationId,
                    'data' => ['id' => $itemId],
                ], 201);
            });
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Consumable creation failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to create consumable',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * PUT /consumables/{id} — обновление расходника.
     */
    public function update(int $id, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            return $this->db->transaction(function () use ($id, $request, $correlationId): JsonResponse {
                $updated = $this->db->table('beauty_consumables')
                    ->where('id', $id)
                    ->update(array_filter([
                        'name' => $request->input('name'),
                        'quantity' => $request->input('quantity'),
                        'min_stock' => $request->input('min_stock'),
                        'cost_price' => $request->input('cost_price'),
                        'correlation_id' => $correlationId,
                        'updated_at' => now(),
                    ]));

                if ($updated === 0) {
                    return $this->response->json([
                        'success' => false,
                        'message' => 'Consumable not found',
                        'correlation_id' => $correlationId,
                    ], 404);
                }

                $this->logger->channel('audit')->info('Consumable updated', [
                    'correlation_id' => $correlationId,
                    'consumable_id' => $id,
                ]);

                return $this->response->json([
                    'success' => true,
                    'message' => 'Consumable updated',
                    'correlation_id' => $correlationId,
                ], 200);
            });
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Consumable update failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to update consumable',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * DELETE /consumables/{id} — удаление расходника.
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            return $this->db->transaction(function () use ($id, $correlationId): JsonResponse {
                $deleted = $this->db->table('beauty_consumables')->where('id', $id)->delete();

                if ($deleted === 0) {
                    return $this->response->json([
                        'success' => false,
                        'message' => 'Consumable not found',
                        'correlation_id' => $correlationId,
                    ], 404);
                }

                $this->logger->channel('audit')->info('Consumable deleted', [
                    'correlation_id' => $correlationId,
                    'consumable_id' => $id,
                ]);

                return $this->response->json([
                    'success' => true,
                    'message' => 'Consumable deleted',
                    'correlation_id' => $correlationId,
                ], 200);
            });
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Consumable deletion failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to delete consumable',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
}
