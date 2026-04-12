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
 * Beauty Master API Controller — CRUD + portfolio + schedule.
 */
class MasterController extends Controller
{
    public function __construct(
        private readonly FraudControlService $fraudService,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
        private readonly Guard $guard,
        private readonly ResponseFactory $response,
    ) {}

    /**
     * GET /masters — список мастеров (публичный).
     */
    public function index(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            $query = $this->db->table('beauty_masters')
                ->where('is_active', true);

            if ($request->filled('salon_id')) {
                $query->where('salon_id', (int) $request->input('salon_id'));
            }

            if ($request->filled('specialization')) {
                $query->where('specialization', 'like', '%' . $request->input('specialization') . '%');
            }

            $masters = $query->orderBy('rating', 'desc')
                ->paginate((int) $request->input('per_page', 20));

            return $this->response->json([
                'success' => true,
                'correlation_id' => $correlationId,
                'data' => $masters->items(),
                'meta' => [
                    'current_page' => $masters->currentPage(),
                    'last_page' => $masters->lastPage(),
                    'total' => $masters->total(),
                ],
            ], 200);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Masters list failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to retrieve masters',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * GET /masters/{id} — профиль мастера (публичный).
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            $master = $this->db->table('beauty_masters')
                ->where('id', $id)
                ->where('is_active', true)
                ->first();

            if ($master === null) {
                return $this->response->json([
                    'success' => false,
                    'message' => 'Master not found',
                    'correlation_id' => $correlationId,
                ], 404);
            }

            $salon = $this->db->table('beauty_salons')
                ->where('id', $master->salon_id)
                ->first(['id', 'name', 'address']);

            return $this->response->json([
                'success' => true,
                'correlation_id' => $correlationId,
                'data' => [
                    'master' => $master,
                    'salon' => $salon,
                ],
            ], 200);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Master show failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to retrieve master',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * GET /masters/{id}/portfolio — портфолио мастера (публичный).
     */
    public function portfolio(int $id, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            $master = $this->db->table('beauty_masters')->where('id', $id)->first();

            if ($master === null) {
                return $this->response->json([
                    'success' => false,
                    'message' => 'Master not found',
                    'correlation_id' => $correlationId,
                ], 404);
            }

            $portfolio = $this->db->table('beauty_portfolio_items')
                ->where('master_id', $id)
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();

            return $this->response->json([
                'success' => true,
                'correlation_id' => $correlationId,
                'data' => [
                    'master_name' => $master->full_name,
                    'items' => $portfolio,
                ],
            ], 200);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Master portfolio failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to retrieve portfolio',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * GET /masters/{id}/schedule — расписание мастера (публичный).
     */
    public function schedule(int $id, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            $master = $this->db->table('beauty_masters')->where('id', $id)->first();

            if ($master === null) {
                return $this->response->json([
                    'success' => false,
                    'message' => 'Master not found',
                    'correlation_id' => $correlationId,
                ], 404);
            }

            $date = $request->input('date', now()->toDateString());

            $bookedSlots = $this->db->table('beauty_appointments')
                ->where('master_id', $id)
                ->whereDate('appointment_datetime', $date)
                ->whereNotIn('status', ['cancelled'])
                ->pluck('appointment_datetime')
                ->map(fn ($dt) => substr((string) $dt, 11, 5))
                ->toArray();

            $slots = [];
            for ($hour = 9; $hour < 21; $hour++) {
                foreach (['00', '30'] as $min) {
                    $slot = sprintf('%02d:%s', $hour, $min);
                    $slots[] = [
                        'time' => $slot,
                        'available' => !in_array($slot, $bookedSlots, true),
                    ];
                }
            }

            return $this->response->json([
                'success' => true,
                'correlation_id' => $correlationId,
                'data' => [
                    'master' => $master->full_name,
                    'date' => $date,
                    'slots' => $slots,
                ],
            ], 200);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Master schedule failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to retrieve schedule',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * POST /masters — создание мастера (auth + manage-beauty-business).
     */
    public function store(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            return $this->db->transaction(function () use ($request, $correlationId): JsonResponse {
                $this->fraudService->scoreOperation([
                    'type' => 'master_create',
                    'user_id' => auth()->id(),
                    'ip_address' => $request->ip(),
                    'correlation_id' => $correlationId,
                ]);

                $masterId = $this->db->table('beauty_masters')->insertGetId([
                    'tenant_id' => (int) $request->header('X-Tenant-ID', '0'),
                    'salon_id' => $request->integer('salon_id'),
                    'uuid' => Str::uuid()->toString(),
                    'correlation_id' => $correlationId,
                    'full_name' => $request->input('full_name'),
                    'specialization' => $request->input('specialization'),
                    'rating' => 5.0,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->logger->channel('audit')->info('Master created', [
                    'correlation_id' => $correlationId,
                    'master_id' => $masterId,
                    'user_id' => auth()->id(),
                ]);

                return $this->response->json([
                    'success' => true,
                    'message' => 'Master created',
                    'correlation_id' => $correlationId,
                    'data' => ['id' => $masterId],
                ], 201);
            });
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Master creation failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to create master',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * PUT /masters/{master} — обновление мастера.
     */
    public function update(int $master, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            return $this->db->transaction(function () use ($master, $request, $correlationId): JsonResponse {
                $updated = $this->db->table('beauty_masters')
                    ->where('id', $master)
                    ->update(array_filter([
                        'full_name' => $request->input('full_name'),
                        'specialization' => $request->input('specialization'),
                        'correlation_id' => $correlationId,
                        'updated_at' => now(),
                    ]));

                if ($updated === 0) {
                    return $this->response->json([
                        'success' => false,
                        'message' => 'Master not found',
                        'correlation_id' => $correlationId,
                    ], 404);
                }

                $this->logger->channel('audit')->info('Master updated', [
                    'correlation_id' => $correlationId,
                    'master_id' => $master,
                ]);

                return $this->response->json([
                    'success' => true,
                    'message' => 'Master updated',
                    'correlation_id' => $correlationId,
                ], 200);
            });
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Master update failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to update master',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * DELETE /masters/{master} — деактивация мастера.
     */
    public function destroy(int $master, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            return $this->db->transaction(function () use ($master, $correlationId): JsonResponse {
                $updated = $this->db->table('beauty_masters')
                    ->where('id', $master)
                    ->update(['is_active' => false, 'correlation_id' => $correlationId, 'updated_at' => now()]);

                if ($updated === 0) {
                    return $this->response->json([
                        'success' => false,
                        'message' => 'Master not found',
                        'correlation_id' => $correlationId,
                    ], 404);
                }

                $this->logger->channel('audit')->info('Master deactivated', [
                    'correlation_id' => $correlationId,
                    'master_id' => $master,
                ]);

                return $this->response->json([
                    'success' => true,
                    'message' => 'Master deactivated',
                    'correlation_id' => $correlationId,
                ], 200);
            });
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Master deletion failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to delete master',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
}
