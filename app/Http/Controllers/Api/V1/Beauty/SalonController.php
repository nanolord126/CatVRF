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
 * Beauty Salon API Controller — CRUD + availability.
 *
 * Public: index, show, availability
 * Auth + manage-beauty-business: store, update, destroy
 */
class SalonController extends Controller
{
    public function __construct(
        private readonly FraudControlService $fraudService,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
        private readonly Guard $guard,
        private readonly ResponseFactory $response,
    ) {}

    /**
     * GET /salons — список салонов (публичный).
     */
    public function index(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        $tenantId = $request->header('X-Tenant-ID');

        try {
            $query = $this->db->table('beauty_salons')
                ->where('is_active', true);

            if ($tenantId !== null) {
                $query->where('tenant_id', (int) $tenantId);
            }

            if ($request->filled('city')) {
                $query->where('address', 'like', '%' . $request->input('city') . '%');
            }

            if ($request->filled('search')) {
                $query->where('name', 'like', '%' . $request->input('search') . '%');
            }

            $salons = $query->orderBy('rating', 'desc')
                ->paginate((int) $request->input('per_page', 20));

            return $this->response->json([
                'success' => true,
                'correlation_id' => $correlationId,
                'data' => $salons->items(),
                'meta' => [
                    'current_page' => $salons->currentPage(),
                    'last_page' => $salons->lastPage(),
                    'per_page' => $salons->perPage(),
                    'total' => $salons->total(),
                ],
            ], 200);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Salon list failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to retrieve salons',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * GET /salons/{id} — детали салона (публичный).
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            $salon = $this->db->table('beauty_salons')
                ->where('id', $id)
                ->where('is_active', true)
                ->first();

            if ($salon === null) {
                return $this->response->json([
                    'success' => false,
                    'message' => 'Salon not found',
                    'correlation_id' => $correlationId,
                ], 404);
            }

            $masters = $this->db->table('beauty_masters')
                ->where('salon_id', $salon->id)
                ->where('is_active', true)
                ->get(['id', 'full_name', 'specialization', 'rating']);

            return $this->response->json([
                'success' => true,
                'correlation_id' => $correlationId,
                'data' => [
                    'salon' => $salon,
                    'masters' => $masters,
                ],
            ], 200);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Salon show failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to retrieve salon',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * GET /salons/{id}/availability — доступные слоты.
     */
    public function availability(int $id, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            $salon = $this->db->table('beauty_salons')->where('id', $id)->first();

            if ($salon === null) {
                return $this->response->json([
                    'success' => false,
                    'message' => 'Salon not found',
                    'correlation_id' => $correlationId,
                ], 404);
            }

            $date = $request->input('date', now()->toDateString());

            $masters = $this->db->table('beauty_masters')
                ->where('salon_id', $id)
                ->where('is_active', true)
                ->get();

            $bookedSlots = $this->db->table('beauty_appointments')
                ->where('beauty_salon_id', $id)
                ->whereDate('appointment_datetime', $date)
                ->whereNotIn('status', ['cancelled'])
                ->pluck('appointment_datetime')
                ->map(fn ($dt) => substr((string) $dt, 11, 5))
                ->toArray();

            $allSlots = [];
            for ($hour = 9; $hour < 21; $hour++) {
                foreach (['00', '30'] as $min) {
                    $slot = sprintf('%02d:%s', $hour, $min);
                    $allSlots[] = [
                        'time' => $slot,
                        'available' => !in_array($slot, $bookedSlots, true),
                    ];
                }
            }

            return $this->response->json([
                'success' => true,
                'correlation_id' => $correlationId,
                'data' => [
                    'date' => $date,
                    'masters_count' => $masters->count(),
                    'slots' => $allSlots,
                ],
            ], 200);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Salon availability failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to retrieve availability',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * POST /salons — создание салона (auth + manage-beauty-business).
     */
    public function store(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            return $this->db->transaction(function () use ($request, $correlationId): JsonResponse {
                $fraudResult = $this->fraudService->scoreOperation([
                    'type' => 'salon_create',
                    'user_id' => auth()->id(),
                    'ip_address' => $request->ip(),
                    'correlation_id' => $correlationId,
                ]);

                if (($fraudResult['decision'] ?? '') === 'block') {
                    return $this->response->json([
                        'success' => false,
                        'message' => 'Operation blocked by fraud control',
                        'correlation_id' => $correlationId,
                    ], 403);
                }

                $salonId = $this->db->table('beauty_salons')->insertGetId([
                    'tenant_id' => (int) $request->header('X-Tenant-ID', '0'),
                    'uuid' => Str::uuid()->toString(),
                    'correlation_id' => $correlationId,
                    'name' => $request->input('name'),
                    'address' => $request->input('address'),
                    'lat' => $request->input('lat'),
                    'lon' => $request->input('lon'),
                    'status' => 'active',
                    'is_active' => true,
                    'tags' => json_encode($request->input('tags', []), JSON_THROW_ON_ERROR),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->logger->channel('audit')->info('Salon created', [
                    'correlation_id' => $correlationId,
                    'salon_id' => $salonId,
                    'user_id' => auth()->id(),
                ]);

                return $this->response->json([
                    'success' => true,
                    'message' => 'Salon created successfully',
                    'correlation_id' => $correlationId,
                    'data' => ['id' => $salonId],
                ], 201);
            });
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Salon creation failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to create salon',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * PUT /salons/{salon} — обновление салона.
     */
    public function update(int $salon, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            return $this->db->transaction(function () use ($salon, $request, $correlationId): JsonResponse {
                $existing = $this->db->table('beauty_salons')->where('id', $salon)->first();

                if ($existing === null) {
                    return $this->response->json([
                        'success' => false,
                        'message' => 'Salon not found',
                        'correlation_id' => $correlationId,
                    ], 404);
                }

                $updateData = array_filter([
                    'name' => $request->input('name'),
                    'address' => $request->input('address'),
                    'lat' => $request->input('lat'),
                    'lon' => $request->input('lon'),
                    'correlation_id' => $correlationId,
                    'updated_at' => now(),
                ]);

                $this->db->table('beauty_salons')->where('id', $salon)->update($updateData);

                $this->logger->channel('audit')->info('Salon updated', [
                    'correlation_id' => $correlationId,
                    'salon_id' => $salon,
                    'user_id' => auth()->id(),
                ]);

                return $this->response->json([
                    'success' => true,
                    'message' => 'Salon updated',
                    'correlation_id' => $correlationId,
                ], 200);
            });
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Salon update failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to update salon',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * DELETE /salons/{salon} — деактивация салона.
     */
    public function destroy(int $salon, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            return $this->db->transaction(function () use ($salon, $correlationId): JsonResponse {
                $updated = $this->db->table('beauty_salons')
                    ->where('id', $salon)
                    ->update([
                        'is_active' => false,
                        'status' => 'deactivated',
                        'correlation_id' => $correlationId,
                        'updated_at' => now(),
                    ]);

                if ($updated === 0) {
                    return $this->response->json([
                        'success' => false,
                        'message' => 'Salon not found',
                        'correlation_id' => $correlationId,
                    ], 404);
                }

                $this->logger->channel('audit')->info('Salon deactivated', [
                    'correlation_id' => $correlationId,
                    'salon_id' => $salon,
                    'user_id' => auth()->id(),
                ]);

                return $this->response->json([
                    'success' => true,
                    'message' => 'Salon deactivated',
                    'correlation_id' => $correlationId,
                ], 200);
            });
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Salon deletion failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to delete salon',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
}


