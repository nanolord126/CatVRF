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
 * Beauty Service API Controller — услуги салонов красоты.
 */
class ServiceController extends Controller
{
    public function __construct(
        private readonly FraudControlService $fraudService,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
        private readonly Guard $guard,
        private readonly ResponseFactory $response,
    ) {}

    /**
     * GET /services — список услуг (публичный).
     */
    public function index(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            $query = $this->db->table('beauty_services');

            if ($request->filled('salon_id')) {
                $query->where('salon_id', (int) $request->input('salon_id'));
            }

            if ($request->filled('category')) {
                $query->where('category', $request->input('category'));
            }

            $services = $query->orderBy('name')
                ->paginate((int) $request->input('per_page', 20));

            return $this->response->json([
                'success' => true,
                'correlation_id' => $correlationId,
                'data' => $services->items(),
                'meta' => [
                    'current_page' => $services->currentPage(),
                    'last_page' => $services->lastPage(),
                    'total' => $services->total(),
                ],
            ], 200);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Services list failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to retrieve services',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * GET /services/{id} — детали услуги (публичный).
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            $service = $this->db->table('beauty_services')->where('id', $id)->first();

            if ($service === null) {
                return $this->response->json([
                    'success' => false,
                    'message' => 'Service not found',
                    'correlation_id' => $correlationId,
                ], 404);
            }

            return $this->response->json([
                'success' => true,
                'correlation_id' => $correlationId,
                'data' => $service,
            ], 200);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Service show failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to retrieve service',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * POST /services — создание услуги (auth + manage-beauty-business).
     */
    public function store(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            return $this->db->transaction(function () use ($request, $correlationId): JsonResponse {
                $this->fraudService->scoreOperation([
                    'type' => 'service_create',
                    'user_id' => auth()->id(),
                    'ip_address' => $request->ip(),
                    'correlation_id' => $correlationId,
                ]);

                $serviceId = $this->db->table('beauty_services')->insertGetId([
                    'tenant_id' => (int) $request->header('X-Tenant-ID', '0'),
                    'salon_id' => $request->integer('salon_id'),
                    'uuid' => Str::uuid()->toString(),
                    'correlation_id' => $correlationId,
                    'name' => $request->input('name'),
                    'category' => $request->input('category'),
                    'price' => $request->integer('price'),
                    'duration_minutes' => $request->integer('duration_minutes', 60),
                    'description' => $request->input('description', ''),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->logger->channel('audit')->info('Beauty service created', [
                    'correlation_id' => $correlationId,
                    'service_id' => $serviceId,
                    'user_id' => auth()->id(),
                ]);

                return $this->response->json([
                    'success' => true,
                    'message' => 'Service created',
                    'correlation_id' => $correlationId,
                    'data' => ['id' => $serviceId],
                ], 201);
            });
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Service creation failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to create service',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * PUT /services/{service} — обновление услуги.
     */
    public function update(int $service, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            return $this->db->transaction(function () use ($service, $request, $correlationId): JsonResponse {
                $updated = $this->db->table('beauty_services')
                    ->where('id', $service)
                    ->update(array_filter([
                        'name' => $request->input('name'),
                        'category' => $request->input('category'),
                        'price' => $request->input('price'),
                        'duration_minutes' => $request->input('duration_minutes'),
                        'description' => $request->input('description'),
                        'correlation_id' => $correlationId,
                        'updated_at' => now(),
                    ]));

                if ($updated === 0) {
                    return $this->response->json([
                        'success' => false,
                        'message' => 'Service not found',
                        'correlation_id' => $correlationId,
                    ], 404);
                }

                $this->logger->channel('audit')->info('Beauty service updated', [
                    'correlation_id' => $correlationId,
                    'service_id' => $service,
                ]);

                return $this->response->json([
                    'success' => true,
                    'message' => 'Service updated',
                    'correlation_id' => $correlationId,
                ], 200);
            });
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Service update failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to update service',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * DELETE /services/{service} — удаление услуги.
     */
    public function destroy(int $service, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            return $this->db->transaction(function () use ($service, $correlationId): JsonResponse {
                $deleted = $this->db->table('beauty_services')->where('id', $service)->delete();

                if ($deleted === 0) {
                    return $this->response->json([
                        'success' => false,
                        'message' => 'Service not found',
                        'correlation_id' => $correlationId,
                    ], 404);
                }

                $this->logger->channel('audit')->info('Beauty service deleted', [
                    'correlation_id' => $correlationId,
                    'service_id' => $service,
                ]);

                return $this->response->json([
                    'success' => true,
                    'message' => 'Service deleted',
                    'correlation_id' => $correlationId,
                ], 200);
            });
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Service deletion failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to delete service',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
}

