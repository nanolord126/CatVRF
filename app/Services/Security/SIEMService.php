<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Models\SecurityEvent;
use App\Models\User;
use App\Services\FraudControlService;
use Illuminate\Cache\CacheManager;
use Illuminate\Support\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

final class SIEMService
{
    private const CACHE_TTL_MINUTES = 5;

    public function __construct(
        private readonly FraudControlService $fraudControl,
        private readonly CacheManager $cache,
    ) {}

    /**
     * Log a security event
     */
    public function logEvent(array $data): SecurityEvent
    {
        $eventId = Str::uuid()->toString();
        $correlationId = $data['correlation_id'] ?? Str::uuid()->toString();

        $event = SecurityEvent::create([
            'event_id' => $eventId,
            'user_id' => $data['user_id'] ?? null,
            'tenant_id' => $data['tenant_id'] ?? null,
            'event_type' => $data['event_type'],
            'severity' => $data['severity'] ?? 'info',
            'source_ip' => $data['source_ip'] ?? request()?->ip(),
            'user_agent' => $data['user_agent'] ?? request()?->userAgent(),
            'metadata' => $data['metadata'] ?? null,
            'correlation_id' => $correlationId,
            'detected_at' => $data['detected_at'] ?? CarbonImmutable::now(),
        ]);

        // Invalidate cache
        $this->clearCache();

        return $event;
    }

    /**
     * Get security events summary for dashboard
     */
    public function getSummary(int $hours = 24): array
    {
        $cacheKey = "siem:summary:{$hours}";

        return $this->cache->remember($cacheKey, self::CACHE_TTL_MINUTES * 60, function () use ($hours) {
            $since = CarbonImmutable::now()->subHours($hours);

            return [
                'total_events' => SecurityEvent::where('detected_at', '>=', $since)->count(),
                'critical_events' => SecurityEvent::where('detected_at', '>=', $since)
                    ->where('severity', 'critical')
                    ->count(),
                'unresolved_events' => SecurityEvent::where('detected_at', '>=', $since)
                    ->where('resolved', false)
                    ->count(),
                'by_severity' => $this->getEventsBySeverity($since),
                'by_type' => $this->getEventsByType($since),
                'by_tenant' => $this->getEventsByTenant($since),
                'recent_events' => $this->getRecentEvents(10),
            ];
        });
    }

    /**
     * Get recent security events
     */
    public function getRecentEvents(int $limit = 20): array
    {
        return SecurityEvent::with(['user:id,name,email', 'tenant:id,name'])
            ->orderByDesc('detected_at')
            ->limit($limit)
            ->get()
            ->map(fn ($event) => [
                'id' => $event->id,
                'event_id' => $event->event_id,
                'event_type' => $event->event_type,
                'severity' => $event->severity,
                'source_ip' => $event->source_ip,
                'user' => $event->user ? [
                    'id' => $event->user->id,
                    'name' => $event->user->name,
                    'email' => $event->user->email,
                ] : null,
                'tenant' => $event->tenant ? [
                    'id' => $event->tenant->id,
                    'name' => $event->tenant->name,
                ] : null,
                'detected_at' => $event->detected_at->toISOString(),
                'resolved' => $event->resolved,
            ])
            ->toArray();
    }

    /**
     * Resolve a security event
     */
    public function resolveEvent(int $eventId, string $resolvedBy, ?string $notes = null): bool
    {
        $event = SecurityEvent::findOrFail($eventId);

        $updated = $event->update([
            'resolved' => true,
            'resolved_at' => CarbonImmutable::now(),
            'resolved_by' => $resolvedBy,
            'resolution_notes' => $notes,
        ]);

        if ($updated) {
            $this->clearCache();
        }

        return $updated;
    }

    /**
     * Get security events for a specific user
     */
    public function getUserEvents(int $userId, int $limit = 50): array
    {
        return SecurityEvent::where('user_id', $userId)
            ->with('tenant:id,name')
            ->orderByDesc('detected_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get security events for a specific tenant
     */
    public function getTenantEvents(int $tenantId, int $hours = 24, int $limit = 100): array
    {
        return SecurityEvent::where('tenant_id', $tenantId)
            ->where('detected_at', '>=', CarbonImmutable::now()->subHours($hours))
            ->orderByDesc('detected_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get security events timeline for visualization
     */
    public function getTimeline(int $hours = 24, int $intervalMinutes = 60): array
    {
        $cacheKey = "siem:timeline:{$hours}:{$intervalMinutes}";

        return $this->cache->remember($cacheKey, self::CACHE_TTL_MINUTES * 60, function () use ($hours, $intervalMinutes) {
            $since = CarbonImmutable::now()->subHours($hours);
            $interval = $intervalMinutes * 60; // Convert to seconds

            $events = SecurityEvent::where('detected_at', '>=', $since)
                ->selectRaw("FLOOR(UNIX_TIMESTAMP(detected_at) / {$interval}) * {$interval} as timestamp, severity, COUNT(*) as count")
                ->groupByRaw("FLOOR(UNIX_TIMESTAMP(detected_at) / {$interval}) * {$interval}, severity")
                ->orderBy('timestamp')
                ->get()
                ->groupBy('severity')
                ->map(fn ($group) => [
                    'severity' => $group->first()->severity,
                    'data' => $group->map(fn ($item) => [
                        'timestamp' => $item->timestamp,
                        'datetime' => date('Y-m-d H:i:s', $item->timestamp),
                        'count' => $item->count,
                    ])->values(),
                ])->values();

            return $events->toArray();
        });
    }

    /**
     * Get attack chain reconstruction for correlation
     */
    public function getAttackChain(string $correlationId): array
    {
        return SecurityEvent::where('correlation_id', $correlationId)
            ->with(['user:id,name,email', 'tenant:id,name'])
            ->orderBy('detected_at')
            ->get()
            ->map(fn ($event) => [
                'event_id' => $event->event_id,
                'event_type' => $event->event_type,
                'severity' => $event->severity,
                'detected_at' => $event->detected_at->toISOString(),
                'metadata' => $event->metadata,
            ])
            ->toArray();
    }

    /**
     * Get high-risk tenants (most security events)
     */
    public function getHighRiskTenants(int $hours = 24, int $limit = 10): array
    {
        return SecurityEvent::where('detected_at', '>=', CarbonImmutable::now()->subHours($hours))
            ->where('severity', '!=', 'info')
            ->whereNotNull('tenant_id')
            ->selectRaw('tenant_id, COUNT(*) as count, SUM(CASE WHEN severity = "critical" THEN 1 ELSE 0 END) as critical_count')
            ->groupBy('tenant_id')
            ->orderByDesc('count')
            ->orderByDesc('critical_count')
            ->limit($limit)
            ->with('tenant:id,name')
            ->get()
            ->map(fn ($event) => [
                'tenant_id' => $event->tenant_id,
                'tenant_name' => $event->tenant?->name,
                'total_events' => $event->count,
                'critical_events' => $event->critical_count,
                'risk_score' => $this->calculateTenantRiskScore($event->count, $event->critical_count),
            ])
            ->toArray();
    }

    /**
     * Get security metrics for Grafana
     */
    public function getMetricsForGrafana(int $hours = 24): array
    {
        $since = CarbonImmutable::now()->subHours($hours);

        return [
            'events_total' => SecurityEvent::where('detected_at', '>=', $since)->count(),
            'events_by_severity' => $this->getEventsBySeverity($since),
            'events_by_type' => $this->getEventsByType($since),
            'unresolved_critical' => SecurityEvent::where('detected_at', '>=', $since)
                ->where('severity', 'critical')
                ->where('resolved', false)
                ->count(),
            'avg_resolution_time_minutes' => $this->getAvgResolutionTime($since),
        ];
    }

    /**
     * Get events grouped by severity
     */
    private function getEventsBySeverity(Carbon $since): array
    {
        return SecurityEvent::where('detected_at', '>=', $since)
            ->selectRaw('severity, COUNT(*) as count')
            ->groupBy('severity')
            ->pluck('count', 'severity')
            ->toArray();
    }

    /**
     * Get events grouped by type
     */
    private function getEventsByType(Carbon $since): array
    {
        return SecurityEvent::where('detected_at', '>=', $since)
            ->selectRaw('event_type, COUNT(*) as count')
            ->groupBy('event_type')
            ->orderByDesc('count')
            ->limit(10)
            ->pluck('count', 'event_type')
            ->toArray();
    }

    /**
     * Get events grouped by tenant
     */
    private function getEventsByTenant(Carbon $since): array
    {
        return SecurityEvent::where('detected_at', '>=', $since)
            ->whereNotNull('tenant_id')
            ->selectRaw('tenant_id, COUNT(*) as count')
            ->groupBy('tenant_id')
            ->orderByDesc('count')
            ->limit(10)
            ->with('tenant:id,name')
            ->get()
            ->map(fn ($event) => [
                'tenant_id' => $event->tenant_id,
                'tenant_name' => $event->tenant?->name,
                'count' => $event->count,
            ])
            ->toArray();
    }

    /**
     * Clear all SIEM caches
     */
    private function clearCache(): void
    {
        $this->cache->forget('siem:summary:1');
        $this->cache->forget('siem:summary:24');
        $this->cache->forget('siem:summary:168');
        $this->cache->forget('siem:timeline:24:60');
        $this->cache->forget('siem:timeline:168:360');
    }

    /**
     * Calculate tenant risk score (0-100)
     */
    private function calculateTenantRiskScore(int $totalEvents, int $criticalEvents): int
    {
        $baseScore = min($totalEvents * 2, 50); // Max 50 points from total events
        $criticalScore = min($criticalEvents * 10, 50); // Max 50 points from critical events

        return min($baseScore + $criticalScore, 100);
    }

    /**
     * Get average resolution time in minutes
     */
    private function getAvgResolutionTime(Carbon $since): ?float
    {
        return SecurityEvent::where('detected_at', '>=', $since)
            ->where('resolved', true)
            ->whereNotNull('resolved_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, detected_at, resolved_at)) as avg_minutes')
            ->value('avg_minutes');
    }
}
