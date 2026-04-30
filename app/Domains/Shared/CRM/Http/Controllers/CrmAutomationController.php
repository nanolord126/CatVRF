<?php

declare(strict_types=1);

namespace App\Domains\CRM\Http\Controllers;

use App\Domains\CRM\DTOs\CreateCrmAutomationDto;
use App\Domains\CRM\Models\CrmAutomation;
use App\Domains\CRM\Services\CrmAutomationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

/**
 * CRM Automation API Controller — управление автоматизациями.
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmAutomationController extends Controller
{
    public function __construct(
        private readonly CrmAutomationService $automationService,
    ) {}

    /**
     * GET /api/v1/crm/automations — список автоматизаций.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->get('tenant_id', 0);

        $automations = CrmAutomation::query()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return new JsonResponse([
            'success' => true,
            'data' => $automations,
            'correlation_id' => $request->header('X-Correlation-ID', Str::uuid()->toString()),
        ]);
    }

    /**
     * POST /api/v1/crm/automations — создать автоматизацию.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|integer|exists:tenants,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'trigger_type' => 'required|string',
            'trigger_conditions' => 'required|array',
            'actions' => 'required|array',
        ]);

        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $dto = new CreateCrmAutomationDto(
            tenantId: (int) $validated['tenant_id'],
            correlationId: $correlationId,
            name: $validated['name'],
            description: $validated['description'] ?? null,
            triggerType: $validated['trigger_type'],
            triggerConditions: $validated['trigger_conditions'],
            actions: $validated['actions'],
        );

        $automation = $this->automationService->createAutomation($dto);

        return new JsonResponse([
            'success' => true,
            'data' => $automation,
            'correlation_id' => $correlationId,
        ], 201);
    }

    /**
     * POST /api/v1/crm/automations/{id}/toggle — вкл/выкл.
     */
    public function toggle(Request $request, int $id): JsonResponse
    {
        $automation = CrmAutomation::query()->findOrFail($id);
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $this->automationService->toggleAutomation($automation, $correlationId);

        return new JsonResponse([
            'success' => true,
            'data' => $automation->fresh(),
            'correlation_id' => $correlationId,
        ]);
    }
}
