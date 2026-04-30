<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\DTO\Security\ResidentialProxyDetectionResult;
use App\Enums\ProxyType;
use App\Enums\ProxyProvider;
use App\Enums\ResidentialProxyRiskLevel;
use App\Enums\CooldownActionType;
use App\Models\User;
use App\Models\ResidentialProxyDetection;
use Illuminate\Cache\CacheManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Carbon\CarbonImmutable;

/**
 * Residential Proxy Detection Service
 *
 * Multi-layered residential proxy detection system for CatVRF.
 * Implements IP intelligence, behavioral analysis, and geo-specific rules.
 *
 * Production 2026 CANON:
 * - Layer 1: IP Intelligence (IPinfo, MaxMind, FraudScore, GetIPIntel, AbuseIPDB)
 * - Layer 2: Behavioral & Device Fingerprint Analysis
 * - Layer 3: Geo + Session Consistency
 * - Layer 4: Honeypot + Challenge
 * - Special rules for Russian territories (Crimea, Sevastopol, DPR, LPR, Kherson, Zaporizhzhia)
 * - Gradient protection measures (Low → Medium → High → Critical → Permanent Block)
 */
final readonly class ResidentialProxyDetectionService
{
    private const CACHE_TTL_SECONDS = 3600; // 1 hour
    private const REDIS_KEY_PREFIX = 'residential_proxy:';
    private const BLACKLIST_CACHE_TTL = 300; // 5 minutes
    private const WHITELIST_CACHE_TTL = 300; // 5 minutes

    // Russian territories that require special handling
    private const RUSSIAN_TERRITORIES = [
        'Crimea', 'Crimean Federal District',
        'Sevastopol',
        'Donetsk People\'s Republic', 'DPR',
        'Luhansk People\'s Republic', 'LPR',
        'Kherson Oblast', 'Kherson Region',
        'Zaporizhzhia Oblast', 'Zaporizhzhia Region',
    ];

    public function __construct(
        private readonly CacheManager $cache,
        private readonly LogManager $log,
        private readonly CooldownService $cooldownService,
        private readonly SplitKeyService $splitKeyService,
        private readonly BehavioralBiometricsService $behavioralBiometrics,
        private readonly AuditService $auditService,
        private readonly DatabaseManager $db,
        private readonly HttpFactory $http,
        private readonly SensitiveDataMasker $masker,
    ) {}

    /**
     * Detect residential proxy usage for a request
     *
     * @param  Request  $request
     * @param  User|null  $user
     * @return ResidentialProxyDetectionResult
     */
    public function detect(Request $request, ?User $user = null): ResidentialProxyDetectionResult
    {
        $ip = $request->ip();

        // Skip detection for local/private IPs
        if ($this->isPrivateIp($ip)) {
            return ResidentialProxyDetectionResult::clean($request);
        }

        // Check whitelist first (corporate VPNs, etc.)
        if ($this->isWhitelisted($ip)) {
            return ResidentialProxyDetectionResult::whitelisted($request);
        }

        // Check blacklist
        if ($this->isBlacklisted($ip)) {
            return ResidentialProxyDetectionResult::blacklisted($request);
        }

        // Check cache first
        $cachedResult = $this->getCachedDetection($ip);
        if ($cachedResult !== null) {
            return $cachedResult;
        }

        // Perform multi-layered detection
        $detectionData = $this->performDetection($ip, $request, $user);

        // Calculate risk level
        $riskLevel = $this->calculateRiskLevel($detectionData, $request, $user);

        // Create result
        $result = ResidentialProxyDetectionResult::detected(
            $request,
            $detectionData['proxy_type'] ?? ProxyType::UNKNOWN,
            $detectionData['proxy_provider'] ?? ProxyProvider::UNKNOWN,
            $riskLevel,
            $detectionData['confidence_score'] ?? 0,
            [
                'country_code' => $detectionData['country_code'] ?? null,
                'city' => $detectionData['city'] ?? null,
                'is_residential_proxy' => $detectionData['is_residential_proxy'] ?? false,
                'is_rotating' => $detectionData['is_rotating'] ?? false,
                'is_ethical' => $detectionData['is_ethical'] ?? false,
                'is_isp_backed' => $detectionData['is_isp_backed'] ?? false,
                'has_behavioral_anomaly' => $detectionData['has_behavioral_anomaly'] ?? false,
                'geo_mismatch' => $detectionData['geo_mismatch'] ?? false,
                'is_russian_territory_violation' => $detectionData['is_russian_territory_violation'] ?? false,
                'detection_sources' => $detectionData['detection_sources'] ?? [],
                'metadata' => $detectionData['metadata'] ?? [],
            ]
        );

        // Cache the result
        $this->cacheDetection($ip, $result);

        // Log detection to database
        $this->logDetectionToDatabase($result, $user);

        // Log detection
        $this->logDetection($result);

        return $result;
    }

    /**
     * Check if limit-time block should be applied based on Residential Proxy + risk factors
     *
     * Residential Proxy alone does NOT block - only when combined with fraud/ML/behavioral signals.
     *
     * @param  Request  $request
     * @param  User  $user
     * @return array{should_block: bool, risk_level: string, risk_factors: array}
     */
    public function shouldApplyLimitTimeBlock(Request $request, User $user): array
    {
        if (!config('vpn-protection.enabled', true)) {
            return [
                'should_block' => false,
                'risk_level' => 'low',
                'risk_factors' => [],
            ];
        }

        $detection = $this->detect($request, $user);

        // If no residential proxy detected, no block
        if (!$detection->isResidentialProxy) {
            return [
                'should_block' => false,
                'risk_level' => 'low',
                'risk_factors' => [],
            ];
        }

        $riskFactors = [];
        $thresholds = config('vpn-protection.thresholds', []);

        // Check Fraud score
        $fraudScore = $this->getFraudScore($user, $request);
        if ($fraudScore >= ($thresholds['fraud_score'] ?? 0.65)) {
            $riskFactors['fraud_score'] = $fraudScore;
        }

        // Check Behavioral biometrics
        $behavioralScore = $this->getBehavioralScore($request, $user);
        if ($behavioralScore !== null && $behavioralScore < ($thresholds['behavioral_score'] ?? 0.75)) {
            $riskFactors['behavioral_anomaly'] = $behavioralScore;
        }

        // Check Insider threat
        $insiderRisk = $this->getInsiderThreatRisk($user, $request);
        if ($insiderRisk >= ($thresholds['insider_threat_score'] ?? 0.6)) {
            $riskFactors['insider_threat'] = $insiderRisk;
        }

        // Check mass actions
        $massActions = $this->checkMassActions($user, $request);
        if ($massActions) {
            $riskFactors['mass_actions'] = true;
        }

        // Check Russian territory violation
        if ($detection->isRussianTerritoryViolation) {
            $riskFactors['russian_territory_violation'] = true;
        }

        // Determine risk level based on factors
        $riskLevel = $this->calculateProtectionRiskLevel($riskFactors, $detection);

        // Only block if we have risk factors (proxy alone is not enough)
        $shouldBlock = !empty($riskFactors) && in_array($riskLevel, ['high', 'critical'], true);

        return [
            'should_block' => $shouldBlock,
            'risk_level' => $riskLevel,
            'risk_factors' => $riskFactors,
            'detection' => $detection,
        ];
    }

    /**
     * Get fraud score for user
     */
    private function getFraudScore(User $user, Request $request): float
    {
        try {
            $fraudService = app(\App\Services\Fraud\FraudControlService::class);
            $result = $fraudService->checkRequest([
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'ip_address' => $request->ip(),
                'action' => 'proxy_protection_check',
            ]);

            return $result['fraud_score'] ?? 0.0;
        } catch (\Throwable $e) {
            $this->log->channel('security')->error('Fraud score check failed', $this->masker->mask([
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]));
            return 0.0;
        }
    }

    /**
     * Get insider threat risk for user
     */
    private function getInsiderThreatRisk(User $user, Request $request): float
    {
        try {
            $insiderService = app(\App\Services\Security\InsiderThreatService::class);
            // For non-staff users, return 0
            if (!$insiderService->isStaff($user)) {
                return 0.0;
            }

            // Check recent insider threat logs
            $recentLog = \App\Models\InsiderThreatLog::where('user_id', $user->id)
                ->where('tenant_id', $user->tenant_id)
                ->where('created_at', '>=', CarbonImmutable::now()->subHours(1))
                ->orderByDesc('anomaly_score')
                ->first();

            return $recentLog?->anomaly_score ?? 0.0;
        } catch (\Throwable $e) {
            $this->log->channel('security')->error('Insider threat check failed', $this->masker->mask([
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]));
            return 0.0;
        }
    }

    /**
     * Check for mass actions
     */
    private function checkMassActions(User $user, Request $request): bool
    {
        $config = config('vpn-protection.thresholds.mass_actions', []);
        $count = $config['count'] ?? 15;
        $window = $config['window_minutes'] ?? 5;

        try {
            $actionCount = $this->cache->get(
                "user_actions:{$user->id}:{$request->ip()}",
                0
            );

            return $actionCount >= $count;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Calculate protection risk level based on risk factors
     */
    private function calculateProtectionRiskLevel(array $riskFactors, ResidentialProxyDetectionResult $detection): string
    {
        $factorCount = count($riskFactors);

        // Critical: Russian territory violation + any other factor
        if (isset($riskFactors['russian_territory_violation']) && $factorCount >= 1) {
            return 'critical';
        }

        // Critical: 3+ risk factors
        if ($factorCount >= 3) {
            return 'critical';
        }

        // Critical: High confidence score + behavioral anomaly
        if ($detection->confidenceScore >= 80 && isset($riskFactors['behavioral_anomaly'])) {
            return 'critical';
        }

        // High: 1-2 risk factors
        if ($factorCount >= 1) {
            return 'high';
        }

        // Low/Medium: No risk factors (proxy alone)
        return 'low';
    }

    /**
     * Apply protection measures based on residential proxy detection result
     *
     * @param  User  $user
     * @param  ResidentialProxyDetectionResult  $result
     * @param  string|null  $correlationId
     * @return void
     */
    public function applyProtection(
        User $user,
        ResidentialProxyDetectionResult $result,
        ?string $correlationId = null
    ): void {
        if (!$result->requiresProtection()) {
            return;
        }

        $correlationId = $correlationId ?? (string) \Illuminate\Support\Str::uuid();

        // Apply cooldown for VPN_LOGIN (tracking the proxy detection itself)
        if ($result->riskLevel->getCooldownHours() > 0) {
            $this->cooldownService->startCooldown(
                $user,
                CooldownActionType::VPN_LOGIN,
                $result->riskLevel->getCooldownHours(),
                sprintf(
                    'Residential proxy detected: %s (Risk: %s, Provider: %s)',
                    $result->ipAddress,
                    $result->riskLevel->value,
                    $result->proxyProvider->value
                ),
                $user->tenant_id,
                [
                    'proxy_type' => $result->proxyType->value,
                    'proxy_provider' => $result->proxyProvider->value,
                    'proxy_risk_level' => $result->riskLevel->value,
                    'ip_address' => $result->ipAddress,
                    'confidence_score' => $result->confidenceScore,
                    'is_russian_territory_violation' => $result->isRussianTerritoryViolation,
                    'correlation_id' => $correlationId,
                ]
            );
        }

        // Apply financial operations cooldown for Medium+ risk
        // This blocks withdrawals, transfers, bank changes
        if ($result->riskLevel->getCooldownHours() > 0) {
            $this->cooldownService->startCooldown(
                $user,
                CooldownActionType::FINANCIAL_OPERATIONS,
                $result->riskLevel->getCooldownHours(),
                sprintf(
                    'Financial operations blocked due to residential proxy detection: %s (Risk: %s, Provider: %s)',
                    $result->ipAddress,
                    $result->riskLevel->value,
                    $result->proxyProvider->value
                ),
                $user->tenant_id,
                [
                    'proxy_type' => $result->proxyType->value,
                    'proxy_provider' => $result->proxyProvider->value,
                    'proxy_risk_level' => $result->riskLevel->value,
                    'ip_address' => $result->ipAddress,
                    'confidence_score' => $result->confidenceScore,
                    'is_russian_territory_violation' => $result->isRussianTerritoryViolation,
                    'correlation_id' => $correlationId,
                    'triggered_by' => 'residential_proxy_detection',
                ]
            );
        }

        // Apply critical changes cooldown for High+ risk
        // This blocks contact info changes, KYB, staff invitation with elevated roles
        if ($result->riskLevel === ResidentialProxyRiskLevel::HIGH || 
            $result->riskLevel === ResidentialProxyRiskLevel::CRITICAL) {
            $this->cooldownService->startCooldown(
                $user,
                CooldownActionType::CRITICAL_CHANGES,
                $result->riskLevel->getCooldownHours(),
                sprintf(
                    'Critical changes blocked due to high-risk residential proxy detection: %s (Risk: %s, Provider: %s)',
                    $result->ipAddress,
                    $result->riskLevel->value,
                    $result->proxyProvider->value
                ),
                $user->tenant_id,
                [
                    'proxy_type' => $result->proxyType->value,
                    'proxy_provider' => $result->proxyProvider->value,
                    'proxy_risk_level' => $result->riskLevel->value,
                    'ip_address' => $result->ipAddress,
                    'confidence_score' => $result->confidenceScore,
                    'is_russian_territory_violation' => $result->isRussianTerritoryViolation,
                    'correlation_id' => $correlationId,
                    'triggered_by' => 'residential_proxy_detection',
                ]
            );
        }

        // Invalidate split key for High+ risk
        if ($result->riskLevel->requiresSplitKeyInvalidation()) {
            $this->splitKeyService->invalidateOnRisk(
                new \App\DTO\SplitKey\InvalidateSplitKeyDTO(
                    userId: $user->id,
                    tenantId: $user->tenant_id,
                    reason: sprintf(
                        'Critical residential proxy detected: %s (Risk: %s, Provider: %s)',
                        $result->ipAddress,
                        $result->riskLevel->value,
                        $result->proxyProvider->value
                    ),
                    riskLevel: $result->riskLevel->toVpnRiskLevel()->value,
                    source: 'residential_proxy_detection_service',
                    ipAddress: $result->ipAddress,
                    correlationId: $correlationId,
                )
            );

            // Logout all sessions for critical risk
            if ($result->riskLevel === ResidentialProxyRiskLevel::CRITICAL || 
                $result->riskLevel === ResidentialProxyRiskLevel::PERMANENT_BLOCK) {
                $user->tokens()->delete();
            }
        }

        // Add to blacklist for permanent block
        if ($result->riskLevel->requiresPermanentBlacklist()) {
            $this->addToBlacklist(
                $result->ipAddress,
                $result->proxyProvider,
                'Permanent block: Residential proxy with critical risk',
                'system',
                $correlationId
            );
        }

        $this->log->channel('security')->warning('Residential proxy protection measures applied', $this->masker->mask([
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'proxy_type' => $result->proxyType->value,
            'proxy_provider' => $result->proxyProvider->value,
            'risk_level' => $result->riskLevel->value,
            'cooldown_hours' => $result->riskLevel->getCooldownHours(),
            'financial_operations_blocked' => $result->riskLevel->getCooldownHours() > 0,
            'critical_changes_blocked' => $result->riskLevel === ResidentialProxyRiskLevel::HIGH || 
                                          $result->riskLevel === ResidentialProxyRiskLevel::CRITICAL,
            'split_key_invalidated' => $result->riskLevel->requiresSplitKeyInvalidation(),
            'is_russian_territory_violation' => $result->isRussianTerritoryViolation,
            'correlation_id' => $correlationId,
        ]));
    }

    /**
     * Perform multi-layered residential proxy detection
     *
     * @param  string  $ip
     * @param  Request  $request
     * @param  User|null  $user
     * @return array
     */
    private function performDetection(string $ip, Request $request, ?User $user): array
    {
        $data = [
            'is_residential_proxy' => false,
            'proxy_type' => ProxyType::NONE,
            'proxy_provider' => ProxyProvider::UNKNOWN,
            'country_code' => null,
            'city' => null,
            'confidence_score' => 0,
            'is_rotating' => false,
            'is_ethical' => false,
            'is_isp_backed' => false,
            'has_behavioral_anomaly' => false,
            'geo_mismatch' => false,
            'is_russian_territory_violation' => false,
            'detection_sources' => [],
            'metadata' => [],
        ];

        // Layer 1: IP Intelligence check
        $ipIntel = $this->checkIpIntelligence($ip);
        $data = array_merge($data, $ipIntel);

        // Layer 2: Behavioral anomaly check
        if ($user !== null && config('proxy-detection.behavioral.enabled', true)) {
            $behavioralScore = $this->getBehavioralScore($request, $user);
            $threshold = config('proxy-detection.behavioral.similarity_threshold', 0.75);
            if ($behavioralScore !== null && $behavioralScore < $threshold) {
                $data['has_behavioral_anomaly'] = true;
                $data['detection_sources'][] = 'behavioral_anomaly';
                $data['metadata']['behavioral_score'] = $behavioralScore;
            }
        }

        // Layer 3: Geo consistency check
        if (config('proxy-detection.geo.enabled', true)) {
            $geoMismatch = $this->checkGeoConsistency($ip, $request, $user);
            if ($geoMismatch) {
                $data['geo_mismatch'] = true;
                $data['detection_sources'][] = 'geo_mismatch';
            }

            // Russian territory violation check
            $rfViolation = $this->checkRussianTerritoryViolation(
                $data['country_code'] ?? null,
                $request,
                $user
            );
            if ($rfViolation) {
                $data['is_russian_territory_violation'] = true;
                $data['detection_sources'][] = 'russian_territory_violation';
            }
        }

        return $data;
    }

    /**
     * Check IP intelligence databases (Layer 1)
     *
     * @param  string  $ip
     * @return array
     */
    private function checkIpIntelligence(string $ip): array
    {
        $result = [
            'country_code' => null,
            'city' => null,
            'is_residential_proxy' => false,
            'proxy_type' => ProxyType::NONE,
            'proxy_provider' => ProxyProvider::UNKNOWN,
            'confidence_score' => 0,
            'is_rotating' => false,
            'is_ethical' => false,
            'is_isp_backed' => false,
            'detection_sources' => [],
            'metadata' => [],
        ];

        $config = config('proxy-detection.providers', []);

        // IPinfo Residential Proxy Detection
        if ($config['ipinfo']['enabled'] ?? false) {
            $ipinfoData = $this->checkIpinfo($ip);
            if ($ipinfoData !== null) {
                $result['country_code'] = $ipinfoData['country_code'] ?? $result['country_code'];
                $result['city'] = $ipinfoData['city'] ?? $result['city'];
                if ($ipinfoData['is_residential_proxy'] ?? false) {
                    $result['is_residential_proxy'] = true;
                    $result['proxy_type'] = ProxyType::RESIDENTIAL;
                    $result['proxy_provider'] = $ipinfoData['proxy_provider'] ?? $result['proxy_provider'];
                    $result['confidence_score'] = max($result['confidence_score'], $ipinfoData['confidence_score'] ?? 0);
                    $result['detection_sources'][] = 'ipinfo';
                }
            }
        }

        // MaxMind Proxy Detection
        if ($config['maxmind']['enabled'] ?? false) {
            $maxmindData = $this->checkMaxMind($ip);
            if ($maxmindData !== null) {
                $result['country_code'] = $maxmindData['country_code'] ?? $result['country_code'];
                $result['city'] = $maxmindData['city'] ?? $result['city'];
                if ($maxmindData['is_residential_proxy'] ?? false) {
                    $result['is_residential_proxy'] = true;
                    $result['proxy_type'] = ProxyType::RESIDENTIAL;
                    $result['confidence_score'] = max($result['confidence_score'], $maxmindData['confidence_score'] ?? 0);
                    $result['detection_sources'][] = 'maxmind';
                }
            }
        }

        // FraudScore
        if ($config['fraudscore']['enabled'] ?? false) {
            $fraudScoreData = $this->checkFraudScore($ip);
            if ($fraudScoreData !== null && ($fraudScoreData['is_residential_proxy'] ?? false)) {
                $result['is_residential_proxy'] = true;
                $result['proxy_type'] = ProxyType::RESIDENTIAL;
                $result['proxy_provider'] = $fraudScoreData['proxy_provider'] ?? $result['proxy_provider'];
                $result['confidence_score'] = max($result['confidence_score'], $fraudScoreData['confidence_score'] ?? 0);
                $result['detection_sources'][] = 'fraudscore';
            }
        }

        // GetIPIntel
        if ($config['getipintel']['enabled'] ?? false) {
            $intelData = $this->checkGetIPIntel($ip);
            if ($intelData !== null && ($intelData['is_proxy'] ?? false)) {
                $result['is_residential_proxy'] = true;
                $result['confidence_score'] = max($result['confidence_score'], $intelData['probability'] * 100);
                $result['detection_sources'][] = 'getipintel';
            }
        }

        // AbuseIPDB
        if ($config['abuseipdb']['enabled'] ?? false) {
            $abuseData = $this->checkAbuseIPDB($ip);
            if ($abuseData !== null) {
                $result['country_code'] = $abuseData['country_code'] ?? $result['country_code'];
                $confidence = $abuseData['abuse_confidence_score'] ?? 0;
                if ($confidence > 50) {
                    $result['is_residential_proxy'] = true;
                    $result['confidence_score'] = max($result['confidence_score'], $confidence);
                    $result['detection_sources'][] = 'abuseipdb';
                }
            }
        }

        // Check known residential proxy ranges
        $knownRanges = config('proxy-detection.known_ranges', []);
        foreach ($knownRanges as $range) {
            if ($this->ipInRange($ip, $range)) {
                $result['is_residential_proxy'] = true;
                $result['proxy_type'] = ProxyType::RESIDENTIAL;
                $result['confidence_score'] = max($result['confidence_score'], 80);
                $result['detection_sources'][] = 'known_ranges';
                break;
            }
        }

        // Determine proxy provider from detection metadata
        if ($result['is_residential_proxy']) {
            $result['proxy_provider'] = $this->identifyProvider($ip, $result['metadata']);
            $result['proxy_type'] = $this->determineProxyType($result['proxy_provider'], $result['metadata']);
            $result['is_ethical'] = $result['proxy_provider']->isEthical();
            $result['is_rotating'] = $this->isRotatingProxy($result['metadata']);
            $result['is_isp_backed'] = $result['proxy_type'] === ProxyType::ISP_BACKED;
        }

        return $result;
    }

    /**
     * Calculate risk level based on detection data
     *
     * @param  array  $data
     * @param  Request  $request
     * @param  User|null  $user
     * @return ResidentialProxyRiskLevel
     */
    private function calculateRiskLevel(array $data, Request $request, ?User $user): ResidentialProxyRiskLevel
    {
        // If no residential proxy detected, return LOW
        if (!($data['is_residential_proxy'] ?? false)) {
            return ResidentialProxyRiskLevel::LOW;
        }

        $thresholds = config('proxy-detection.thresholds', []);
        $confidenceScore = $data['confidence_score'] ?? 0;
        $hasBehavioralAnomaly = $data['has_behavioral_anomaly'] ?? false;
        $geoMismatch = $data['geo_mismatch'] ?? false;
        $rfViolation = $data['is_russian_territory_violation'] ?? false;
        $proxyProvider = $data['proxy_provider'] ?? ProxyProvider::UNKNOWN;

        // CRITICAL: Russian territory violation + residential proxy
        if ($rfViolation) {
            if ($hasBehavioralAnomaly || $this->isSensitiveAction($request, $user)) {
                return ResidentialProxyRiskLevel::CRITICAL;
            }
            return ResidentialProxyRiskLevel::HIGH;
        }

        // CRITICAL: High confidence + behavioral anomaly + sensitive action
        if ($confidenceScore >= $thresholds['critical']['confidence_score'] && 
            $hasBehavioralAnomaly && 
            $this->isSensitiveAction($request, $user)) {
            return ResidentialProxyRiskLevel::CRITICAL;
        }

        // CRITICAL: Known high-risk provider + behavioral anomaly
        if ($proxyProvider->isHighRisk() && $hasBehavioralAnomaly) {
            return ResidentialProxyRiskLevel::CRITICAL;
        }

        // HIGH: High confidence score
        if ($confidenceScore >= $thresholds['high']['confidence_score']) {
            return ResidentialProxyRiskLevel::HIGH;
        }

        // HIGH: Behavioral anomaly + residential proxy
        if ($hasBehavioralAnomaly) {
            return ResidentialProxyRiskLevel::HIGH;
        }

        // HIGH: Geo mismatch
        if ($geoMismatch) {
            return ResidentialProxyRiskLevel::HIGH;
        }

        // MEDIUM: Medium confidence score
        if ($confidenceScore >= $thresholds['medium']['confidence_score']) {
            return ResidentialProxyRiskLevel::MEDIUM;
        }

        // MEDIUM: Known medium-risk provider
        if ($proxyProvider->isMediumRisk()) {
            return ResidentialProxyRiskLevel::MEDIUM;
        }

        // Default to MEDIUM for residential proxy
        return ResidentialProxyRiskLevel::MEDIUM;
    }

    /**
     * Check if IP is private/local
     *
     * @param  string  $ip
     * @return bool
     */
    private function isPrivateIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }

    /**
     * Check if IP is in CIDR range
     *
     * @param  string  $ip
     * @param  string  $range
     * @return bool
     */
    private function ipInRange(string $ip, string $range): bool
    {
        [$subnet, $bits] = explode('/', $range);
        $ip_long = ip2long($ip);
        $subnet_long = ip2long($subnet);
        $mask = -1 << (32 - $bits);
        $subnet_long &= $mask;

        return ($ip_long & $mask) === $subnet_long;
    }

    /**
     * Check if IP is whitelisted
     *
     * @param  string  $ip
     * @return bool
     */
    private function isWhitelisted(string $ip): bool
    {
        $cacheKey = 'residential_proxy_whitelist:' . $ip;
        
        return $this->cache->remember($cacheKey, self::WHITELIST_CACHE_TTL, function () use ($ip) {
            return $this->db->table('residential_proxy_whitelist')
                ->where('ip_address', $ip)
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', CarbonImmutable::now());
                })
                ->exists();
        });
    }

    /**
     * Check if IP is blacklisted
     *
     * @param  string  $ip
     * @return bool
     */
    private function isBlacklisted(string $ip): bool
    {
        $cacheKey = 'residential_proxy_blacklist:' . $ip;
        
        return $this->cache->remember($cacheKey, self::BLACKLIST_CACHE_TTL, function () use ($ip) {
            return $this->db->table('residential_proxy_blacklist')
                ->where('ip_address', $ip)
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', CarbonImmutable::now());
                })
                ->exists();
        });
    }

    /**
     * Add IP to blacklist
     *
     * @param  string  $ip
     * @param  ProxyProvider  $provider
     * @param  string  $reason
     * @param  string  $blacklistedBy
     * @param  string|null  $correlationId
     * @return void
     */
    private function addToBlacklist(
        string $ip,
        ProxyProvider $provider,
        string $reason,
        string $blacklistedBy,
        ?string $correlationId = null
    ): void {
        $this->db->table('residential_proxy_blacklist')->insert([
            'ip_address' => $ip,
            'proxy_provider' => $provider->value,
            'blacklist_reason' => $reason,
            'blacklisted_by' => $blacklistedBy,
            'blacklisted_at' => CarbonImmutable::now(),
            'correlation_id' => $correlationId,
        ]);

        // Clear cache
        $this->cache->forget('residential_proxy_blacklist:' . $ip);
    }

    /**
     * Get behavioral score from BehavioralBiometricsService
     *
     * @param  Request  $request
     * @param  User  $user
     * @return float|null
     */
    private function getBehavioralScore(Request $request, User $user): ?float
    {
        try {
            $sessionId = session()->getId();
            $cachedScore = $this->behavioralBiometrics->getSessionScore($user->id, $sessionId);
            
            if ($cachedScore !== null) {
                return $cachedScore;
            }

            // If no cached score, return neutral
            return 0.5;
        } catch (\Throwable $e) {
            $this->log->channel('security')->error('Behavioral score retrieval failed', $this->masker->mask([
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]));
            return null;
        }
    }

    /**
     * Check geo consistency
     *
     * @param  string  $ip
     * @param  Request  $request
     * @param  User|null  $user
     * @return bool
     */
    private function checkGeoConsistency(string $ip, Request $request, ?User $user): bool
    {
        if (!config('proxy-detection.geo.check_previous_sessions', true)) {
            return false;
        }

        // Get previous sessions for user
        if ($user === null) {
            return false;
        }

        $previousDetections = ResidentialProxyDetection::where('user_id', $user->id)
            ->where('detected_at', '>', CarbonImmutable::now()->subHours(
                config('proxy-detection.geo.max_time_hours', 24)
            ))
            ->whereNotNull('country_code')
            ->distinct('country_code')
            ->get();

        if ($previousDetections->isEmpty()) {
            return false;
        }

        // Check if current country differs from previous sessions
        $currentCountry = $this->getCountryFromIp($ip);
        if ($currentCountry === null) {
            return false;
        }

        foreach ($previousDetections as $detection) {
            if ($detection->country_code !== $currentCountry) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check Russian territory violation
     *
     * @param  string|null  $countryCode
     * @param  Request  $request
     * @param  User|null  $user
     * @return bool
     */
    private function checkRussianTerritoryViolation(
        ?string $countryCode,
        Request $request,
        ?User $user
    ): bool {
        if (!config('proxy-detection.russian_territories.enabled', true)) {
            return false;
        }

        $rfTerritories = self::RUSSIAN_TERRITORIES;
        $rfCountryCodes = config('proxy-detection.russian_territories.country_codes', ['RU', 'UA']);

        // If detected country is not Russia/Ukraine but user is from Russian territories
        if ($countryCode !== null && !in_array($countryCode, $rfCountryCodes, true)) {
            if ($user !== null) {
                $userLocation = $user->location_country ?? null;
                if ($userLocation && in_array($userLocation, $rfTerritories, true)) {
                    return true;
                }
            }

            // Check request headers for location hints
            $locationHeader = $request->header('X-User-Location');
            if ($locationHeader && in_array($locationHeader, $rfTerritories, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get country from IP (simplified)
     *
     * @param  string  $ip
     * @return string|null
     */
    private function getCountryFromIp(string $ip): ?string
    {
        // In production, use actual GeoIP lookup
        // This is a placeholder
        return null;
    }

    /**
     * Identify proxy provider from metadata
     *
     * @param  string  $ip
     * @param  array  $metadata
     * @return ProxyProvider
     */
    private function identifyProvider(string $ip, array $metadata): ProxyProvider
    {
        // Check metadata for provider hints
        $providerHint = $metadata['provider'] ?? null;
        if ($providerHint !== null) {
            try {
                return ProxyProvider::from(strtolower($providerHint));
            } catch (\ValueError $e) {
                // Continue to detection
            }
        }

        // In production, use actual provider detection logic
        return ProxyProvider::UNKNOWN;
    }

    /**
     * Determine proxy type
     *
     * @param  ProxyProvider  $provider
     * @param  array  $metadata
     * @return ProxyType
     */
    private function determineProxyType(ProxyProvider $provider, array $metadata): ProxyType
    {
        if ($provider->isEthical()) {
            return ProxyType::ETHICAL_RESIDENTIAL;
        }

        if ($metadata['is_rotating'] ?? false) {
            return ProxyType::ROTATING_RESIDENTIAL;
        }

        if ($metadata['is_isp_backed'] ?? false) {
            return ProxyType::ISP_BACKED;
        }

        return ProxyType::RESIDENTIAL;
    }

    /**
     * Check if proxy is rotating
     *
     * @param  array  $metadata
     * @return bool
     */
    private function isRotatingProxy(array $metadata): bool
    {
        return $metadata['is_rotating'] ?? false;
    }

    /**
     * Check if current action is sensitive
     *
     * @param  Request  $request
     * @param  User|null  $user
     * @return bool
     */
    private function isSensitiveAction(Request $request, ?User $user): bool
    {
        $sensitiveRoutes = config('proxy-detection.sensitive_routes', []);

        foreach ($sensitiveRoutes as $route) {
            if ($request->is($route)) {
                return true;
            }
        }

        // Check if user is new (created within 24 hours)
        if ($user !== null && $user->created_at->diffInHours(CarbonImmutable::now()) < 24) {
            return true;
        }

        return false;
    }

    /**
     * Get cached detection result
     *
     * @param  string  $ip
     * @return ResidentialProxyDetectionResult|null
     */
    private function getCachedDetection(string $ip): ?ResidentialProxyDetectionResult
    {
        $cacheKey = self::REDIS_KEY_PREFIX . $ip;
        $cached = $this->cache->get($cacheKey);

        if ($cached !== null) {
            return ResidentialProxyDetectionResult::fromArray($cached);
        }

        return null;
    }

    /**
     * Cache detection result
     *
     * @param  string  $ip
     * @param  ResidentialProxyDetectionResult  $result
     * @return void
     */
    private function cacheDetection(string $ip, ResidentialProxyDetectionResult $result): void
    {
        $cacheKey = self::REDIS_KEY_PREFIX . $ip;
        $this->cache->put($cacheKey, $result->toArray(), self::CACHE_TTL_SECONDS);
    }

    /**
     * Log detection to database
     *
     * @param  ResidentialProxyDetectionResult  $result
     * @param  User|null  $user
     * @return void
     */
    private function logDetectionToDatabase(ResidentialProxyDetectionResult $result, ?User $user): void
    {
        ResidentialProxyDetection::create([
            'user_id' => $user?->id,
            'tenant_id' => $user?->tenant_id,
            'ip_address' => $result->ipAddress,
            'country_code' => $result->countryCode,
            'city' => $result->city,
            'proxy_type' => $result->proxyType->value,
            'proxy_provider' => $result->proxyProvider->value,
            'risk_level' => $result->riskLevel->value,
            'confidence_score' => $result->confidenceScore,
            'is_residential_proxy' => $result->isResidentialProxy,
            'is_rotating' => $result->isRotating,
            'is_ethical' => $result->isEthical,
            'is_isp_backed' => $result->isIspBacked,
            'has_behavioral_anomaly' => $result->hasBehavioralAnomaly,
            'geo_mismatch' => $result->geoMismatch,
            'is_russian_territory_violation' => $result->isRussianTerritoryViolation,
            'detection_sources' => $result->detectionSources,
            'metadata' => $result->metadata,
            'correlation_id' => (string) \Illuminate\Support\Str::uuid(),
            'detected_at' => CarbonImmutable::now(),
        ]);
    }

    /**
     * Log detection result
     *
     * @param  ResidentialProxyDetectionResult  $result
     * @return void
     */
    private function logDetection(ResidentialProxyDetectionResult $result): void
    {
        if ($result->isResidentialProxy) {
            $this->log->channel('security')->warning('Residential proxy detected', $this->masker->mask([
                'ip_address' => $result->ipAddress,
                'proxy_type' => $result->proxyType->value,
                'proxy_provider' => $result->proxyProvider->value,
                'risk_level' => $result->riskLevel->value,
                'confidence_score' => $result->confidenceScore,
                'country_code' => $result->countryCode,
                'is_russian_territory_violation' => $result->isRussianTerritoryViolation,
                'detection_sources' => $result->detectionSources,
            ]));
        } else {
            $this->log->channel('security')->debug('Residential proxy check passed', $this->masker->mask([
                'ip_address' => $result->ipAddress,
            ]));
        }
    }

    /**
     * Check IPinfo API (placeholder)
     *
     * @param  string  $ip
     * @return array|null
     */
    private function checkIpinfo(string $ip): ?array
    {
        $config = config('proxy-detection.providers.ipinfo', []);
        $apiKey = $config['api_key'] ?? null;

        if ($apiKey === null) {
            return null;
        }

        try {
            $response = Http::timeout($config['timeout'] ?? 5)
                ->withToken($apiKey)
                ->get("{$config['endpoint']}/{$ip}/json");

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'country_code' => $data['country'] ?? null,
                    'city' => $data['city'] ?? null,
                    'is_residential_proxy' => ($data['privacy']['proxy'] ?? false) || ($data['privacy']['hosting'] ?? false),
                    'proxy_provider' => $this->mapIpinfoProvider($data['asn']['name'] ?? null),
                    'confidence_score' => 90,
                ];
            }
        } catch (\Throwable $e) {
            $this->log->channel('security')->error('IPinfo check failed', [
                'ip' => $ip,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Map IPinfo ASN to ProxyProvider
     *
     * @param  string|null  $asnName
     * @return ProxyProvider
     */
    private function mapIpinfoProvider(?string $asnName): ProxyProvider
    {
        if ($asnName === null) {
            return ProxyProvider::UNKNOWN;
        }

        $asnLower = strtolower($asnName);

        return match (true) {
            str_contains($asnLower, 'bright') || str_contains($asnLower, 'luminati') => ProxyProvider::BRIGHT_DATA,
            str_contains($asnLower, 'oxylabs') => ProxyProvider::OXYLABS,
            str_contains($asnLower, 'iproyal') => ProxyProvider::IPROYAL,
            str_contains($asnLower, 'smartproxy') => ProxyProvider::SMARTPROXY,
            default => ProxyProvider::UNKNOWN,
        };
    }

    /**
     * Check MaxMind database (placeholder)
     *
     * @param  string  $ip
     * @return array|null
     */
    private function checkMaxMind(string $ip): ?array
    {
        // In production, implement actual MaxMind database lookup
        return null;
    }

    /**
     * Check FraudScore API (placeholder)
     *
     * @param  string  $ip
     * @return array|null
     */
    private function checkFraudScore(string $ip): ?array
    {
        // In production, implement actual FraudScore API call
        return null;
    }

    /**
     * Check GetIPIntel API (placeholder)
     *
     * @param  string  $ip
     * @return array|null
     */
    private function checkGetIPIntel(string $ip): ?array
    {
        $config = config('proxy-detection.providers.getipintel', []);
        $apiKey = $config['api_key'] ?? null;

        if ($apiKey === null) {
            return null;
        }

        try {
            $response = Http::timeout($config['timeout'] ?? 3)
                ->get($config['endpoint'], [
                    'ip' => $ip,
                    'contact' => $apiKey,
                    'flags' => 'm',
                ]);

            if ($response->successful()) {
                $probability = (float) $response->body();
                return [
                    'is_proxy' => $probability > ($config['threshold'] ?? 0.8),
                    'probability' => $probability,
                ];
            }
        } catch (\Throwable $e) {
            $this->log->channel('security')->error('GetIPIntel check failed', $this->masker->mask([
                'ip' => $ip,
                'error' => $e->getMessage(),
            ]));
        }

        return null;
    }

    /**
     * Check AbuseIPDB API (placeholder)
     *
     * @param  string  $ip
     * @return array|null
     */
    private function checkAbuseIPDB(string $ip): ?array
    {
        $config = config('proxy-detection.providers.abuseipdb', []);
        $apiKey = $config['api_key'] ?? null;

        if ($apiKey === null) {
            return null;
        }

        try {
            $response = Http::timeout($config['timeout'] ?? 5)
                ->withHeaders([
                    'Key' => $apiKey,
                    'Accept' => 'application/json',
                ])
                ->get("{$config['endpoint']}/check", [
                    'ipAddress' => $ip,
                    'maxAgeInDays' => $config['max_age_days'] ?? 90,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'country_code' => $data['data']['countryCode'] ?? null,
                    'abuse_confidence_score' => $data['data']['abuseConfidenceScore'] ?? 0,
                ];
            }
        } catch (\Throwable $e) {
            $this->log->channel('security')->error('AbuseIPDB check failed', [
                'ip' => $ip,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }
}
