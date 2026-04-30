<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Security\SIEMService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;

final class SIEMController extends Controller
{
    public function __construct(
        private readonly AuthManager $auth,
        private readonly SIEMService $siemService,
    ) {}

    /**
     * Get SIEM summary for dashboard
     */
    public function summary(Request $request): JsonResponse
    {
        $hours = (int) $request->get('hours', 24);

        return new JsonResponse([
            'data' => $this->siemService->getSummary($hours),
        ]);
    }

    /**
     * Get recent security events
     */
    public function recent(Request $request): JsonResponse
    {
        $limit = min((int) $request->get('limit', 20), 100);

        return new JsonResponse([
            'data' => $this->siemService->getRecentEvents($limit),
        ]);
    }

    /**
     * Get security events timeline
     */
    public function timeline(Request $request): JsonResponse
    {
        $hours = (int) $request->get('hours', 24);
        $interval = (int) $request->get('interval', 60);

        return new JsonResponse([
            'data' => $this->siemService->getTimeline($hours, $interval),
        ]);
    }

    /**
     * Get attack chain for correlation
     */
    public function attackChain(string $correlationId): JsonResponse
    {
        return new JsonResponse([
            'data' => $this->siemService->getAttackChain($correlationId),
        ]);
    }

    /**
     * Get high-risk tenants
     */
    public function highRiskTenants(Request $request): JsonResponse
    {
        $hours = (int) $request->get('hours', 24);
        $limit = min((int) $request->get('limit', 10), 50);

        return new JsonResponse([
            'data' => $this->siemService->getHighRiskTenants($hours, $limit),
        ]);
    }

    /**
     * Get metrics for Grafana
     */
    public function metrics(Request $request): JsonResponse
    {
        $hours = (int) $request->get('hours', 24);

        return new JsonResponse([
            'data' => $this->siemService->getMetricsForGrafana($hours),
        ]);
    }

    /**
     * Resolve a security event
     */
    public function resolveEvent(Request $request, int $eventId): JsonResponse
    {
        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $resolved = $this->siemService->resolveEvent(
            $eventId,
            $this->auth->id() ? (string) $this->auth->id() : $request->ip(),
            $request->input('notes')
        );

        return new JsonResponse([
            'success' => $resolved,
            'message' => $resolved ? 'Event resolved successfully' : 'Failed to resolve event',
        ]);
    }

    /**
     * Get user security events
     */
    public function userEvents(Request $request): JsonResponse
    {
        $userId = (int) $request->get('user_id', $this->auth->id());
        $limit = min((int) $request->get('limit', 50), 100);

        return new JsonResponse([
            'data' => $this->siemService->getUserEvents($userId, $limit),
        ]);
    }

    /**
     * Get tenant security events
     */
    public function tenantEvents(Request $request): JsonResponse
    {
        $tenantId = (int) $request->get('tenant_id', $this->auth->user()?->tenant_id);
        $hours = (int) $request->get('hours', 24);
        $limit = min((int) $request->get('limit', 100), 200);

        return new JsonResponse([
            'data' => $this->siemService->getTenantEvents($tenantId, $hours, $limit),
        ]);
    }
}
