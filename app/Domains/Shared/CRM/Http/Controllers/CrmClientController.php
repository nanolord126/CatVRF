<?php

declare(strict_types=1);

namespace App\Domains\CRM\Http\Controllers;

use App\Domains\CRM\DTOs\CreateCrmClientDto;
use App\Domains\CRM\DTOs\CreateCrmInteractionDto;
use App\Domains\CRM\Models\CrmClient;
use App\Domains\CRM\Services\CrmAnalyticsService;
use App\Domains\CRM\Services\CrmService;
use App\Providers\CrmServiceProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

/**
 * CRM Client API Controller — CRUD + вертикальные профили.
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmClientController extends Controller
{
    public function __construct(
        private readonly CrmService $crmService,
        private readonly CrmAnalyticsService $analytics,
    ) {}

    /**
     * GET /api/v1/crm/clients — список клиентов.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->get('tenant_id', 0);
        $vertical = $request->get('vertical');
        $status = $request->get('status', 'active');
        $search = $request->get('search');
        $perPage = min((int) $request->get('per_page', 20), 100);

        $query = CrmClient::query()
            ->where('tenant_id', $tenantId)
            ->where('status', $status);

        if ($vertical !== null) {
            $query->where('vertical', $vertical);
        }

        if ($search !== null && $search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('first_name', 'ILIKE', "%{$search}%")
                    ->orWhere('last_name', 'ILIKE', "%{$search}%")
                    ->orWhere('email', 'ILIKE', "%{$search}%")
                    ->orWhere('phone', 'LIKE', "%{$search}%")
                    ->orWhere('company_name', 'ILIKE', "%{$search}%");
            });
        }

        $clients = $query->orderByDesc('last_interaction_at')
            ->paginate($perPage);

        return new JsonResponse([
            'success' => true,
            'data' => $clients,
            'correlation_id' => $request->header('X-Correlation-ID', Str::uuid()->toString()),
        ]);
    }

    /**
     * GET /api/v1/crm/clients/{id} — карточка клиента с профилем.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $client = CrmClient::query()
            ->with(['interactions' => fn ($q) => $q->latest()->limit(10), 'segments'])
            ->findOrFail($id);

        $profileData = $client->verticalProfile();

        return new JsonResponse([
            'success' => true,
            'data' => [
                'client' => $client,
                'vertical_profile' => $profileData,
                'stats' => [
                    'total_interactions' => $client->interactions()->count(),
                    'segments_count' => $client->segments()->count(),
                ],
            ],
            'correlation_id' => $request->header('X-Correlation-ID', Str::uuid()->toString()),
        ]);
    }

    /**
     * POST /api/v1/crm/clients — создать клиента.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|integer|exists:tenants,id',
            'first_name' => 'required|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:30',
            'client_type' => 'nullable|string|in:individual,business,vip,wholesale,corporate',
            'vertical' => 'required|string',
            'source' => 'nullable|string',
            'company_name' => 'nullable|string|max:255',
        ]);

        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $dto = new CreateCrmClientDto(
            tenantId: (int) $validated['tenant_id'],
            correlationId: $correlationId,
            firstName: $validated['first_name'],
            lastName: $validated['last_name'] ?? null,
            email: $validated['email'] ?? null,
            phone: $validated['phone'] ?? null,
            clientType: $validated['client_type'] ?? 'individual',
            vertical: $validated['vertical'],
            source: $validated['source'] ?? 'marketplace',
            companyName: $validated['company_name'] ?? null,
        );

        $client = $this->crmService->createClient($dto);

        return new JsonResponse([
            'success' => true,
            'data' => $client,
            'correlation_id' => $correlationId,
        ], 201);
    }

    /**
     * PUT /api/v1/crm/clients/{id} — обновить клиента.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $client = CrmClient::query()->findOrFail($id);
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'email' => 'sometimes|email|max:255',
            'phone' => 'sometimes|string|max:30',
            'status' => 'sometimes|string|in:active,inactive,archived,blacklisted',
            'client_type' => 'sometimes|string|in:individual,business,vip,wholesale,corporate',
            'tags' => 'sometimes|array',
            'preferences' => 'sometimes|array',
            'special_notes' => 'sometimes|array',
        ]);

        $this->crmService->updateClient($client, $validated, $correlationId);

        return new JsonResponse([
            'success' => true,
            'data' => $client->fresh(),
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * POST /api/v1/crm/clients/{id}/interactions — записать взаимодействие.
     */
    public function storeInteraction(Request $request, int $id): JsonResponse
    {
        $client = CrmClient::query()->findOrFail($id);
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $validated = $request->validate([
            'type' => 'required|string',
            'channel' => 'required|string',
            'direction' => 'required|string|in:inbound,outbound',
            'content' => 'required|string|max:5000',
            'metadata' => 'nullable|array',
        ]);

        $dto = new CreateCrmInteractionDto(
            crmClientId: $client->id,
            tenantId: $client->tenant_id,
            correlationId: $correlationId,
            type: $validated['type'],
            channel: $validated['channel'],
            direction: $validated['direction'],
            content: $validated['content'],
            metadata: $validated['metadata'] ?? [],
        );

        $interaction = $this->crmService->recordInteraction($dto);

        return new JsonResponse([
            'success' => true,
            'data' => $interaction,
            'correlation_id' => $correlationId,
        ], 201);
    }

    /**
     * GET /api/v1/crm/clients/{id}/interactions — история взаимодействий.
     */
    public function interactions(Request $request, int $id): JsonResponse
    {
        $client = CrmClient::query()->findOrFail($id);
        $perPage = min((int) $request->get('per_page', 20), 100);

        $interactions = $client->interactions()
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return new JsonResponse([
            'success' => true,
            'data' => $interactions,
            'correlation_id' => $request->header('X-Correlation-ID', Str::uuid()->toString()),
        ]);
    }

    /**
     * GET /api/v1/crm/analytics/dashboard — дашборд-метрики.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $tenantId = (int) $request->get('tenant_id', 0);
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $metrics = $this->analytics->getDashboardMetrics($tenantId, $correlationId);

        return new JsonResponse([
            'success' => true,
            'data' => $metrics,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * GET /api/v1/crm/clients/sleeping — спящие клиенты.
     */
    public function sleeping(Request $request): JsonResponse
    {
        $tenantId = (int) $request->get('tenant_id', 0);
        $vertical = $request->get('vertical');
        $days = (int) $request->get('days', 60);

        if ($vertical !== null) {
            $service = CrmServiceProvider::resolveVerticalService($vertical);

            if ($service !== null && method_exists($service, 'getSleepingClients')) {
                $clients = $service->getSleepingClients($tenantId, $days);
            } else {
                $clients = CrmClient::query()
                    ->forTenant($tenantId)
                    ->byVertical($vertical)
                    ->sleeping($days)
                    ->get();
            }
        } else {
            $clients = CrmClient::query()
                ->forTenant($tenantId)
                ->sleeping($days)
                ->get();
        }

        return new JsonResponse([
            'success' => true,
            'data' => $clients,
            'correlation_id' => $request->header('X-Correlation-ID', Str::uuid()->toString()),
        ]);
    }
}
