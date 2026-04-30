<?php

declare(strict_types=1);

namespace App\Services\Security;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Cache\CacheManager;

use App\Enums\Role;
use App\Events\InsiderAnomalyDetected;
use App\Models\BehavioralProfile;
use App\Models\InsiderThreatLog;
use App\Models\RiskScoreLog;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Fraud\FraudMLService;
use Illuminate\Database\Eloquent\Collection;
use Carbon\CarbonImmutable;
use Illuminate\Log\LogManager;

final class InsiderThreatService
{
    private const FINANCIAL_THRESHOLD_DEFAULT = 10000; // Default financial threshold in RUB

    private const CLIENT_VIEW_CACHE_HOURS = 25;

    private const CLIENT_HUNTING_ATTEMPT_THRESHOLD = 5;

    private const CLIENT_HUNTING_WINDOW_MINUTES = 5;

    private const CLIENT_ACCESS_FREQUENCY_WINDOW_SECONDS = 60;

    private const CLIENT_ACCESS_FREQUENCY_THRESHOLD = 10;

    public function __construct(
        private readonly EventDispatcher $eventDispatcher,
        private readonly LogManager $log,
        private readonly FraudMLService $fraudML,
        private readonly CacheManager $cache,
        private readonly SensitiveDataMasker $masker,
    ) {}
    /**
     * Analyze staff action for insider threat patterns
     */
    public function analyzeAction(
        User $staff,
        Tenant $tenant,
        string $actionType,
        ?string $resourceType = null,
        ?int $resourceId = null,
        array $actionDetails = [],
        array $context = []
    ): InsiderThreatAnalysisResult {
        // Skip non-staff users
        if (! $this->isStaff($staff)) {
            return InsiderThreatAnalysisResult::safe();
        }

        $anomalyScore = $this->calculateAnomalyScore($staff, $tenant, $actionType, $actionDetails, $context);
        $severity = $this->determineSeverity($anomalyScore);
        $wasBlocked = $this->shouldBlockAction($severity, $anomalyScore);

        // Log the action
        $log = $this->logAction(
            staff: $staff,
            tenant: $tenant,
            actionType: $actionType,
            resourceType: $resourceType,
            resourceId: $resourceId,
            anomalyScore: $anomalyScore,
            severity: $severity,
            wasBlocked: $wasBlocked,
            actionDetails: $actionDetails,
            context: $context,
        );

        // Trigger alert if suspicious
        if ($anomalyScore >= config('security.insider_threat.alert_threshold', 0.7)) {
            $this->eventDispatcher->dispatch(new InsiderAnomalyDetected(
                staff: $staff,
                tenant: $tenant,
                actionType: $actionType,
                anomalyScore: $anomalyScore,
                severity: $severity,
                log: $log,
            ));
        }

        return new InsiderThreatAnalysisResult(
            anomalyScore: $anomalyScore,
            severity: $severity,
            wasBlocked: $wasBlocked,
            log: $log,
        );
    }

    /**
     * Get high-risk threats for tenant
     */
    public function getHighRiskThreats(Tenant $tenant, int $days = 7): Collection
    {
        return InsiderThreatLog::where('tenant_id', $tenant->id)
            ->highSeverity()
            ->requiresReview()
            ->recent($days)
            ->with(['user', 'performedBy'])
            ->orderByDesc('anomaly_score')
            ->get();
    }

    /**
     * Get threat summary for tenant
     */
    public function getThreatSummary(Tenant $tenant, int $days = 30): array
    {
        $logs = InsiderThreatLog::where('tenant_id', $tenant->id)
            ->recent($days)
            ->get();

        return [
            'total_threats' => $logs->count(),
            'critical' => $logs->where('severity', 'critical')->count(),
            'high' => $logs->where('severity', 'high')->count(),
            'medium' => $logs->where('severity', 'medium')->count(),
            'low' => $logs->where('severity', 'low')->count(),
            'blocked' => $logs->where('was_blocked', true)->count(),
            'requires_review' => $logs->where('requires_review', true)->where('is_reviewed', false)->count(),
            'avg_anomaly_score' => $logs->avg('anomaly_score') ?? 0.0,
        ];
    }

    /**
     * Get user threat profile
     */
    public function getUserThreatProfile(User $user, Tenant $tenant, int $days = 30): array
    {
        $logs = InsiderThreatLog::where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->recent($days)
            ->get();

        return [
            'total_actions' => $logs->count(),
            'anomalous_actions' => $logs->where('anomaly_score', '>=', 0.5)->count(),
            'blocked_actions' => $logs->where('was_blocked', true)->count(),
            'avg_anomaly_score' => $logs->avg('anomaly_score') ?? 0.0,
            'max_anomaly_score' => $logs->max('anomaly_score') ?? 0.0,
            'action_types' => $logs->pluck('action_type')->countBy()->toArray(),
            'severity_distribution' => $logs->pluck('severity')->countBy()->toArray(),
        ];
    }

    /**
     * Calculate anomaly score based on behavioral patterns
     */
    private function calculateAnomalyScore(
        User $staff,
        Tenant $tenant,
        string $actionType,
        array $actionDetails,
        array $context
    ): float {
        $score = 0.0;

        // Pattern 1: Unusual time (outside business hours)
        $score += $this->checkUnusualTime($context);

        // Pattern 2: Unusual location (different IP/geo)
        $score += $this->checkUnusualLocation($staff, $context);

        // Pattern 3: High-frequency actions
        $score += $this->checkHighFrequency($staff, $tenant, $actionType);

        // Pattern 4: Sensitive data access
        $score += $this->checkSensitiveDataAccess($actionType, $actionDetails);

        // Pattern 5: Mass operations (bulk download/export)
        $score += $this->checkMassOperations($actionType, $actionDetails);

        // Pattern 6: Wallet/balance manipulation
        $score += $this->checkFinancialManipulation($actionType, $actionDetails);

        // Pattern 7: Recent termination risk (revoked access)
        $score += $this->checkTerminationRisk($staff);

        // Pattern 8: Behavioral biometrics anomaly (NEW 2026)
        $score += $this->checkBehavioralAnomaly($staff, $context);

        // Integrate with FraudMLService for advanced detection
        $mlScore = $this->getMlAnomalyScore($staff, $tenant, $actionType, $context);
        $score += $mlScore * 0.3; // Weight ML score at 30%

        return min($score, 1.0); // Cap at 1.0
    }

    /**
     * Check if action is performed outside business hours
     */
    private function checkUnusualTime(array $context): float
    {
        $hour = CarbonImmutable::now()->hour;
        $businessHoursStart = config('security.insider_threat.business_hours_start', 9);
        $businessHoursEnd = config('security.insider_threat.business_hours_end', 18);

        if ($hour < $businessHoursStart || $hour >= $businessHoursEnd) {
            return 0.15; // 15% risk for unusual time
        }

        return 0.0;
    }

    /**
     * Check if action is from unusual location
     */
    private function checkUnusualLocation(User $staff, array $context): float
    {
        $currentIp = $context['ip_address'] ?? null;
        if (! $currentIp) {
            return 0.0;
        }

        // Get recent IPs for this user
        $recentIps = InsiderThreatLog::where('user_id', $staff->id)
            ->where('created_at', '>=', CarbonImmutable::now()->subDays(30))
            ->pluck('context')
            ->map(fn ($ctx) => $ctx['ip_address'] ?? null)
            ->filter()
            ->unique()
            ->toArray();

        if (! in_array($currentIp, $recentIps, true)) {
            return 0.25; // 25% risk for new IP
        }

        return 0.0;
    }

    /**
     * Check for high-frequency actions
     */
    private function checkHighFrequency(User $staff, Tenant $tenant, string $actionType): float
    {
        $recentActions = InsiderThreatLog::where('user_id', $staff->id)
            ->where('tenant_id', $tenant->id)
            ->where('action_type', $actionType)
            ->where('created_at', '>=', CarbonImmutable::now()->subMinutes(5))
            ->count();

        $threshold = config('security.insider_threat.frequency_threshold', 10);

        if ($recentActions >= $threshold) {
            return 0.3; // 30% risk for high frequency
        }

        return 0.0;
    }

    /**
     * Check for sensitive data access
     */
    private function checkSensitiveDataAccess(string $actionType, array $actionDetails): float
    {
        $sensitiveActions = [
            'data_export',
            'customer_list_download',
            'financial_report_access',
            'pii_access',
        ];

        if (in_array($actionType, $sensitiveActions, true)) {
            return 0.2; // 20% risk for sensitive data access
        }

        return 0.0;
    }

    /**
     * Check for mass operations (bulk download/export)
     */
    private function checkMassOperations(string $actionType, array $actionDetails): float
    {
        $massOperations = [
            'bulk_export',
            'mass_download',
            'bulk_delete',
        ];

        if (in_array($actionType, $massOperations, true)) {
            $count = $actionDetails['count'] ?? 0;
            $threshold = config('security.insider_threat.mass_operation_threshold', 100);

            if ($count >= $threshold) {
                return 0.35; // 35% risk for mass operations
            }
        }

        return 0.0;
    }

    /**
     * Check for financial manipulation
     */
    private function checkFinancialManipulation(string $actionType, array $actionDetails): float
    {
        $financialActions = [
            'wallet_change',
            'balance_adjustment',
            'refund_processing',
            'commission_change',
        ];

        if (in_array($actionType, $financialActions, true)) {
            $amount = $actionDetails['amount'] ?? 0;
            $threshold = config('security.insider_threat.financial_threshold', 10000);

            if ($amount >= $threshold) {
                return 0.4; // 40% risk for large financial changes
            }

            return 0.15; // 15% risk for any financial change
        }

        return 0.0;
    }

    /**
     * Check if user is at termination risk (recently revoked or soft-deleted)
     */
    private function checkTerminationRisk(User $staff): float
    {
        if ($staff->isRevoked()) {
            return 0.5; // 50% risk for revoked users
        }

        if ($staff->trashed()) {
            return 0.6; // 60% risk for soft-deleted users
        }

        // Check if user was recently inactive
        if ($staff->last_activity_at && $staff->last_activity_at->lt(CarbonImmutable::now()->subDays(30))) {
            return 0.2; // 20% risk for inactive users returning
        }

        return 0.0;
    }

    /**
     * Check behavioral biometrics anomaly (NEW 2026)
     *
     * Analyzes if current behavioral patterns deviate from baseline.
     * This is critical for detecting compromised accounts or insider threats.
     */
    private function checkBehavioralAnomaly(User $staff, array $context): float
    {
        // Get behavioral profile
        $profile = BehavioralProfile::where('user_id', $staff->id)
            ->where('tenant_id', $staff->tenant_id)
            ->first();

        if (! $profile || ! $profile->isMature()) {
            return 0.0; // No baseline yet, skip
        }

        // Get recent behavioral scores from risk_score_logs
        $recentScores = RiskScoreLog::where('user_id', $staff->id)
            ->where('tenant_id', $staff->tenant_id)
            ->where('created_at', '>=', CarbonImmutable::now()->subMinutes(30))
            ->whereNotNull('behavioral_score')
            ->pluck('behavioral_score')
            ->toArray();

        if (empty($recentScores)) {
            return 0.0; // No recent data
        }

        // Calculate average recent behavioral risk
        $avgBehavioralRisk = array_sum($recentScores) / count($recentScores);

        // If behavioral risk is high (> 0.5), add to anomaly score
        if ($avgBehavioralRisk > 0.5) {
            return min(0.35, $avgBehavioralRisk * 0.7); // Cap at 35%
        }

        // Check for sudden drop in behavioral score (possible account takeover)
        if (isset($profile->avg_overall_score) && $profile->avg_overall_score > 0.8) {
            $currentScore = $recentScores[array_key_last($recentScores)] ?? 0.5;
            if ($currentScore < 0.5) {
                return 0.25; // Sudden drop in behavioral similarity
            }
        }

        return 0.0;
    }

    /**
     * Get ML-based anomaly score from FraudMLService
     */
    private function getMlAnomalyScore(User $staff, Tenant $tenant, string $actionType, array $context): float
    {
        try {
            $fraudService = $this->fraudML;

            if (! $fraudService) {
                return 0.0;
            }

            $result = $fraudService->analyzeInsiderAction([
                'user_id' => $staff->id,
                'tenant_id' => $tenant->id,
                'action_type' => $actionType,
                'ip_address' => $context['ip_address'] ?? null,
                'device_fingerprint' => $context['device_fingerprint'] ?? null,
                'timestamp' => CarbonImmutable::now()->toIso8601String(),
            ]);

            return $result['anomaly_score'] ?? 0.0;
        } catch (\Exception $e) {
            $this->log->warning('ML anomaly score calculation failed', $this->masker->mask([
                'error' => $e->getMessage(),
                'user_id' => $staff->id,
            ]));

            return 0.0;
        }
    }

    /**
     * Determine severity based on anomaly score
     */
    private function determineSeverity(float $anomalyScore): string
    {
        return match (true) {
            $anomalyScore >= 0.9 => 'critical',
            $anomalyScore >= 0.7 => 'high',
            $anomalyScore >= 0.5 => 'medium',
            default => 'low',
        };
    }

    /**
     * Determine if action should be blocked
     */
    private function shouldBlockAction(string $severity, float $anomalyScore): bool
    {
        $blockThreshold = config('security.insider_threat.block_threshold', 0.85);

        return $severity === 'critical' || $anomalyScore >= $blockThreshold;
    }

    /**
     * Log action to insider threat log
     */
    private function logAction(
        User $staff,
        Tenant $tenant,
        string $actionType,
        ?string $resourceType,
        ?int $resourceId,
        float $anomalyScore,
        string $severity,
        bool $wasBlocked,
        array $actionDetails,
        array $context
    ): InsiderThreatLog {
        return InsiderThreatLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => $staff->id,
            'performed_by' => auth()->id(),
            'action_type' => $actionType,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'anomaly_score' => $anomalyScore,
            'severity' => $severity,
            'was_blocked' => $wasBlocked,
            'block_reason' => $wasBlocked ? 'High anomaly score detected' : null,
            'requires_review' => $severity !== 'low',
            'action_details' => $actionDetails,
            'context' => array_merge($context, [
                'timestamp' => CarbonImmutable::now()->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Check if user is staff (business role)
     */
    private function isStaff(User $user): bool
    {
        return $user->role?->isBusiness() ?? false;
    }

    // ========================
    // CLIENT DATA PROTECTION METHODS
    // ========================

    /**
     * Analyze client data access for insider threats
     * 
     * @param  User  $staff  Staff member accessing client data
     * @param  Tenant  $tenant  Tenant context
     * @param  string  $accessType  Type of access (view, search, export)
     * @param  int  $recordCount  Number of records accessed
     * @param  array  $context  Additional context
     * @return InsiderThreatAnalysisResult Analysis result
     */
    public function analyzeClientDataAccess(
        User $staff,
        Tenant $tenant,
        string $accessType,
        int $recordCount,
        array $context = []
    ): InsiderThreatAnalysisResult {
        if (! $this->isStaff($staff)) {
            return InsiderThreatAnalysisResult::safe();
        }

        $anomalyScore = 0.0;

        // Check for mass client data access
        $anomalyScore += $this->checkMassClientAccess($staff, $tenant, $accessType, $recordCount);

        // Check for hunting patterns (email/phone searches)
        $anomalyScore += $this->checkClientHunting($staff, $tenant, $context);

        // Check for unusual access patterns
        $anomalyScore += $this->checkUnusualAccessPatterns($staff, $tenant, $accessType);

        // Check for cross-tenant access attempts
        $anomalyScore += $this->checkCrossTenantClientAccess($staff, $tenant, $context);

        // Determine severity and blocking
        $severity = $this->determineSeverity($anomalyScore);
        $wasBlocked = $this->shouldBlockAction($severity, $anomalyScore);

        // Log the access
        $log = $this->logAction(
            staff: $staff,
            tenant: $tenant,
            actionType: 'client_data_'.$accessType,
            resourceType: 'client_data',
            resourceId: null,
            anomalyScore: $anomalyScore,
            severity: $severity,
            wasBlocked: $wasBlocked,
            actionDetails: ['record_count' => $recordCount, 'access_type' => $accessType],
            context: $context,
        );

        // Trigger alert if suspicious
        if ($anomalyScore >= config('insider-protection.behavioral.alert_threshold', 0.6)) {
            $this->eventDispatcher->dispatch(new InsiderAnomalyDetected(
                staff: $staff,
                tenant: $tenant,
                actionType: 'client_data_'.$accessType,
                anomalyScore: $anomalyScore,
                severity: $severity,
                log: $log,
            ));
        }

        return new InsiderThreatAnalysisResult(
            anomalyScore: $anomalyScore,
            severity: $severity,
            wasBlocked: $wasBlocked,
            log: $log,
        );
    }

    /**
     * Track client profile view count
     * 
     * @param  User  $staff  Staff member viewing client
     * @param  Tenant  $tenant  Tenant context
     * @param  int  $clientId  Client user ID
     * @return void
     */
    public function trackClientView(User $staff, Tenant $tenant, int $clientId): void
    {
        if (! $this->isStaff($staff)) {
            return;
        }

        $cacheKey = "client_views:{$staff->id}:{$tenant->id}";
        $views = $this->cache->get($cacheKey, []);
        
        $now = CarbonImmutable::now();
        $hourKey = $now->format('Y-m-d-H');
        $dayKey = $now->format('Y-m-d');

        if (! isset($views[$hourKey])) {
            $views[$hourKey] = [];
        }
        if (! isset($views[$dayKey])) {
            $views[$dayKey] = [];
        }

        $views[$hourKey][] = $clientId;
        $views[$dayKey][] = $clientId;

        // Check thresholds
        $hourlyCount = count($views[$hourKey]);
        $dailyCount = count($views[$dayKey]);

        $maxHourly = config('insider-protection.behavioral.max_client_views_per_hour', 50);
        $maxDaily = config('insider-protection.behavioral.max_client_views_per_day', 200);

        if ($hourlyCount > $maxHourly || $dailyCount > $maxDaily) {
            // Trigger cooldown
            $cooldownService = app(\App\Services\Security\CooldownService::class);
            $cooldownService->startCooldown(
                $staff,
                \App\Enums\CooldownActionType::HUNTING_DETECTED,
                1, // 1 hour
                sprintf('Exceeded client view threshold: %d hourly, %d daily', $hourlyCount, $dailyCount)
            );

            // Log to audit
            $this->log->warning('Client view threshold exceeded', $this->masker->mask([
                'user_id' => $staff->id,
                'tenant_id' => $tenant->id,
                'hourly_count' => $hourlyCount,
                'daily_count' => $dailyCount,
            ]));
        }

        // Store in cache for N hours
        $this->cache->put($cacheKey, $views, self::CLIENT_VIEW_CACHE_HOURS * 60);
    }

    /**
     * Check for mass client data access
     */
    private function checkMassClientAccess(User $staff, Tenant $tenant, string $accessType, int $recordCount): float
    {
        $maxRecords = config('insider-protection.data_access.max_records_per_request.'.$staff->role?->value, 50);

        if ($recordCount > $maxRecords) {
            $ratio = $recordCount / $maxRecords;
            
            return min(0.5, ($ratio - 1) * 0.25); // Up to 50% risk for large excess
        }

        return 0.0;
    }

    /**
     * Check for hunting patterns on client contacts
     */
    private function checkClientHunting(User $staff, Tenant $tenant, array $context): float
    {
        $searchTerm = $context['search_term'] ?? null;
        
        if (! $searchTerm) {
            return 0.0;
        }

        // Check if search term looks like email/phone pattern
        $isEmail = filter_var($searchTerm, FILTER_VALIDATE_EMAIL) !== false;
        $isPhone = preg_match('/^[0-9+()\s-]{10,}$/', $searchTerm);

        if (! $isEmail && ! $isPhone) {
            return 0.0;
        }

        // Track hunting attempts
        $cacheKey = "client_hunting:{$staff->id}:{$tenant->id}";
        $attempts = $this->cache->get($cacheKey, 0);

        if ($attempts >= self::CLIENT_HUNTING_ATTEMPT_THRESHOLD) {
            return 0.4; // 40% risk for repeated hunting
        }

        $this->cache->put($cacheKey, $attempts + 1, self::CLIENT_HUNTING_WINDOW_MINUTES * 60); // N minute window

        return 0.1; // 10% risk for single hunting attempt
    }

    /**
     * Check for unusual access patterns
     */
    private function checkUnusualAccessPatterns(User $staff, Tenant $tenant, string $accessType): float
    {
        $risk = 0.0;

        // Check if staff is accessing client data outside their department
        if ($accessType === 'export' && ! $staff->role?->isPlatformAdmin()) {
            $risk += 0.3; // 30% risk for export by non-admin
        }

        // Check frequency of access
        $cacheKey = "client_access_freq:{$staff->id}:{$accessType}";
        $count = $this->cache->get($cacheKey, 0);

        if ($count >= self::CLIENT_ACCESS_FREQUENCY_THRESHOLD) {
            $risk += 0.2; // 20% risk for high frequency
        }

        $this->cache->put($cacheKey, $count + 1, self::CLIENT_ACCESS_FREQUENCY_WINDOW_SECONDS); // 1 minute window

        return $risk;
    }

    /**
     * Check for cross-tenant client access attempts
     */
    private function checkCrossTenantClientAccess(User $staff, Tenant $tenant, array $context): float
    {
        $targetTenantId = $context['target_tenant_id'] ?? null;

        if (! $targetTenantId) {
            return 0.0;
        }

        // Super-admins can access all tenants
        if ($staff->role?->isPlatformAdmin()) {
            return 0.0;
        }

        // Check if accessing different tenant
        if ($targetTenantId !== $tenant->id) {
            return 0.6; // 60% risk for cross-tenant access
        }

        return 0.0;
    }

    /**
     * Get client access statistics for a staff member
     * 
     * @param  User  $staff  Staff member
     * @param  Tenant  $tenant  Tenant context
     * @param  int  $days  Number of days to analyze
     * @return array Statistics
     */
    public function getClientAccessStats(User $staff, Tenant $tenant, int $days = 7): array
    {
        $logs = InsiderThreatLog::where('tenant_id', $tenant->id)
            ->where('user_id', $staff->id)
            ->where('action_type', 'like', 'client_data_%')
            ->recent($days)
            ->get();

        $totalViews = $logs->where('action_type', 'client_data_view')->count();
        $totalSearches = $logs->where('action_type', 'client_data_search')->count();
        $totalExports = $logs->where('action_type', 'client_data_export')->count();
        
        $totalRecords = $logs->sum(function ($log) {
            return $log->action_details['record_count'] ?? 0;
        });

        return [
            'total_access_events' => $logs->count(),
            'total_views' => $totalViews,
            'total_searches' => $totalSearches,
            'total_exports' => $totalExports,
            'total_records_accessed' => $totalRecords,
            'avg_anomaly_score' => $logs->avg('anomaly_score') ?? 0.0,
            'blocked_actions' => $logs->where('was_blocked', true)->count(),
            'high_risk_events' => $logs->where('anomaly_score', '>=', 0.7)->count(),
        ];
    }
}

// ========================
// DTOs
// ========================

final class InsiderThreatAnalysisResult
{
    public function __construct(
        public readonly float $anomalyScore,
        public readonly string $severity,
        public readonly bool $wasBlocked,
        public readonly ?InsiderThreatLog $log = null,
    ) {}

    public static function safe(): self
    {
        return new self(
            anomalyScore: 0.0,
            severity: 'low',
            wasBlocked: false,
        );
    }

    public function isSafe(): bool
    {
        return $this->anomalyScore < 0.5 && ! $this->wasBlocked;
    }

    public function getBlockReason(): ?string
    {
        return $this->wasBlocked ? $this->log?->block_reason : null;
    }
}
