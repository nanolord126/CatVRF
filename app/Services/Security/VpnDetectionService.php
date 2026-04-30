<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\DTO\Security\VpnDetectionResult;
use App\Enums\VpnRiskLevel;
use App\Models\User;
use App\Services\Security\CooldownService;
use App\Services\Security\SplitKeyService;
use Illuminate\Cache\CacheManager;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Carbon\CarbonImmutable;

/**
 * VPN Detection Service
 *
 * Multi-layered VPN/Proxy/Tor detection system for CatVRF.
 * Implements IP intelligence, behavioral analysis, and geo-specific rules.
 *
 * Production 2026 CANON:
 * - Multi-source IP intelligence (MaxMind, IP2Location, AbuseIPDB, GetIPIntel)
 * - ASN/Hosting/Datacenter detection
 * - Tor/I2P exit node detection
 * - Residential proxy detection
 * - Behavioral anomaly integration
 * - Geo-specific rules for Russian territories
 * - Gradient protection measures based on risk level
 */
final readonly class VpnDetectionService
{
    private const CACHE_TTL_SECONDS = 3600; // 1 hour
    private const REDIS_KEY_PREFIX = 'vpn_detection:';

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
        private readonly ResidentialProxyDetectionService $residentialProxyDetection,
        private readonly GeoTerritoryService $geoTerritoryService,
    ) {}
    private readonly SensitiveDataMasker $masker,
    
    /**
     * Detect VPN/Proxy/Tor usage for a request
     *
     * @param  Request  $request
     * @param  User|null  $user
     * @return VpnDetectionResult
     */
    public function detect(Request $request, ?User $user = null): VpnDetectionResult
    {
        $ip = $request->ip();

        // Skip detection for local/private IPs
        if ($this->isPrivateIp($ip)) {
            return VpnDetectionResult::clean($request);
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
        $result = VpnDetectionResult::detected(
            $request,
            $detectionData['provider'] ?? null,
            $riskLevel,
            [
                'country' => $detectionData['country'] ?? null,
                'city' => $detectionData['city'] ?? null,
                'asn' => $detectionData['asn'] ?? null,
                'isp' => $detectionData['isp'] ?? null,
                'is_tor' => $detectionData['is_tor'] ?? false,
                'is_datacenter' => $detectionData['is_datacenter'] ?? false,
                'is_residential_proxy' => $detectionData['is_residential_proxy'] ?? false,
                'is_corporate_vpn' => $detectionData['is_corporate_vpn'] ?? false,
                'has_behavioral_anomaly' => $detectionData['has_behavioral_anomaly'] ?? false,
                'sources' => $detectionData['sources'] ?? [],
                'metadata' => $detectionData['metadata'] ?? [],
            ]
        );

        // Cache the result
        $this->cacheDetection($ip, $result);

        // Log detection
        $this->logDetection($result);

        return $result;
    }

    /**
     * Check if limit-time block should be applied based on VPN + risk factors
     *
     * VPN/Proxy alone does NOT block - only when combined with fraud/ML/behavioral signals.
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

        // If no VPN detected, no block
        if (!$detection->isVpn) {
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
        $behavioralScore = $this->getBehavioralScore($user, $request);
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

        // Check Russian territory mismatch
        $territoryMismatch = $this->checkRussianTerritoryMismatch($detection, $user, $request);
        if ($territoryMismatch) {
            $riskFactors['russian_territory_mismatch'] = true;
        }

        // Determine risk level based on factors
        $riskLevel = $this->calculateProtectionRiskLevel($riskFactors, $detection);

        // Only block if we have risk factors (VPN alone is not enough)
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
                'action' => 'vpn_protection_check',
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
     * Get behavioral biometrics score for user
     */
    private function getBehavioralScore(User $user, Request $request): ?float
    {
        try {
            $sessionId = session()->getId();
            return $this->behavioralBiometrics->getSessionScore($user->id, $sessionId);
        } catch (\Throwable $e) {
            $this->log->channel('security')->error('Behavioral score check failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return null;
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
     * Check Russian territory mismatch
     */
    private function checkRussianTerritoryMismatch(VpnDetectionResult $detection, User $user, Request $request): bool
    {
        if (!config('vpn-protection.russian_territories.enabled', true)) {
            return false;
        }

        $territories = config('vpn-protection.russian_territories.territories', []);
        $countryCodes = config('vpn-protection.russian_territories.country_codes', ['RU', 'UA']);

        $detectedCountry = $detection->country;

        // If detected country is not RU/UA, check if user is from Russian territories
        if ($detectedCountry !== null && !in_array($detectedCountry, $countryCodes, true)) {
            $userLocation = $user->location_country ?? null;
            if ($userLocation && in_array($userLocation, $territories, true)) {
                return true;
            }

            $locationHeader = $request->header('X-User-Location');
            if ($locationHeader && in_array($locationHeader, $territories, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate protection risk level based on risk factors
     */
    private function calculateProtectionRiskLevel(array $riskFactors, VpnDetectionResult $detection): string
    {
        $factorCount = count($riskFactors);

        // Critical: Multiple risk factors + Russian territory mismatch
        if ($factorCount >= 2 && isset($riskFactors['russian_territory_mismatch'])) {
            return 'critical';
        }

        // Critical: Russian territory mismatch + any other factor
        if (isset($riskFactors['russian_territory_mismatch']) && $factorCount >= 1) {
            return 'critical';
        }

        // Critical: 3+ risk factors
        if ($factorCount >= 3) {
            return 'critical';
        }

        // High: 1-2 risk factors
        if ($factorCount >= 1) {
            return 'high';
        }

        // Low/Medium: No risk factors (VPN alone)
        return 'low';
    }

    /**
     * Apply protection measures based on VPN detection result
     *
     * @param  User  $user
     * @param  VpnDetectionResult  $result
     * @param  string|null  $correlationId
     * @return void
     */
    public function applyProtection(
        User $user,
        VpnDetectionResult $result,
        ?string $correlationId = null
    ): void {
        if (!$result->requiresProtection()) {
            return;
        }

        $correlationId = $correlationId ?? (string) \Illuminate\Support\Str::uuid();

        // Apply cooldown for VPN_LOGIN (tracking the VPN detection itself)
        if ($result->riskLevel->getCooldownHours() > 0) {
            $this->cooldownService->startCooldown(
                $user,
                \App\Enums\CooldownActionType::VPN_LOGIN,
                $result->riskLevel->getCooldownHours(),
                sprintf(
                    'VPN detected: %s (Risk: %s)',
                    $result->provider ?? 'Unknown',
                    $result->riskLevel->value
                ),
                $user->tenant_id,
                [
                    'vpn_provider' => $result->provider,
                    'vpn_risk_level' => $result->riskLevel->value,
                    'ip_address' => $result->ipAddress,
                    'detection_sources' => $result->detectionSources,
                    'correlation_id' => $correlationId,
                ]
            );
        }

        // Apply financial operations cooldown for Medium+ risk
        // This blocks withdrawals, transfers, bank changes
        if ($result->riskLevel->getCooldownHours() > 0) {
            $this->cooldownService->startCooldown(
                $user,
                \App\Enums\CooldownActionType::FINANCIAL_OPERATIONS,
                $result->riskLevel->getCooldownHours(),
                sprintf(
                    'Financial operations blocked due to VPN detection: %s (Risk: %s)',
                    $result->provider ?? 'Unknown',
                    $result->riskLevel->value
                ),
                $user->tenant_id,
                [
                    'vpn_provider' => $result->provider,
                    'vpn_risk_level' => $result->riskLevel->value,
                    'ip_address' => $result->ipAddress,
                    'detection_sources' => $result->detectionSources,
                    'correlation_id' => $correlationId,
                    'triggered_by' => 'vpn_detection',
                ]
            );
        }

        // Apply critical changes cooldown for High+ risk
        // This blocks contact info changes, KYB, staff invitation with elevated roles
        if ($result->riskLevel === \App\Enums\VpnRiskLevel::HIGH || 
            $result->riskLevel === \App\Enums\VpnRiskLevel::CRITICAL) {
            $this->cooldownService->startCooldown(
                $user,
             financial_operations_blocked' => $result->riskLevel->getCooldownHours() > 0,
            'critical_changes_blocked' => $result->riskLevel === \App\Enums\VpnRiskLevel::HIGH || 
                                          $result->riskLevel === \App\Enums\VpnRiskLevel::CRITICAL,
            '   \App\Enums\CooldownActionType::CRITICAL_CHANGES,
                $result->riskLevel->getCooldownHours(),
                sprintf(
                    'Critical changes blocked due to high-risk VPN detection: %s (Risk: %s)',
                    $result->provider ?? 'Unknown',
                    $result->riskLevel->value
                ),
                $user->tenant_id,
                [
                    'vpn_provider' => $result->provider,
                    'vpn_risk_level' => $result->riskLevel->value,
                    'ip_address' => $result->ipAddress,
                    'detection_sources' => $result->detectionSources,
                    'correlation_id' => $correlationId,
                    'triggered_by' => 'vpn_detection',
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
                        'Critical VPN detected: %s (Risk: %s)',
                        $result->provider ?? 'Unknown',
                        $result->riskLevel->value
                    ),
                    riskLevel: $result->riskLevel->value,
                    source: 'vpn_detection_service',
                    ipAddress: $result->ipAddress,
                    correlationId: $correlationId,
                )
            );

            // Logout all sessions for critical risk
            if ($result->riskLevel === VpnRiskLevel::CRITICAL) {
                $user->tokens()->delete();
            }
        }

        $this->log->channel('security')->warning('VPN protection measures applied', $this->masker->mask([
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'vpn_provider' => $result->provider,
            'risk_level' => $result->riskLevel->value,
            'cooldown_hours' => $result->riskLevel->getCooldownHours(),
            'split_key_invalidated' => $result->riskLevel->requiresSplitKeyInvalidation(),
            'correlation_id' => $correlationId,
        ]));
    }

    /**
     * Perform multi-layered VPN detection
     *
     * @param  string  $ip
     * @param  Request  $request
     * @param  User|null  $user
     * @return array
     */
    private function performDetection(string $ip, Request $request, ?User $user): array
    {
        $data = [
            'is_vpn' => false,
            'provider' => null,
            'country' => null,
            'city' => null,
            'asn' => null,
            'isp' => null,
            'is_tor' => false,
            'is_datacenter' => false,
            'is_residential_proxy' => false,
            'is_corporate_vpn' => false,
            'has_behavioral_anomaly' => false,
            'sources' => [],
            'metadata' => [],
        ];

        // 1. IP Intelligence check (MaxMind, IP2Location, etc.)
        $ipIntel = $this->checkIpIntelligence($ip);
        $data = array_merge($data, $ipIntel);

        // 2. ASN/Hosting/Datacenter detection
        $asnData = $this->checkAsnAndHosting($ip);
        $data = array_merge($data, $asnData);

        // 3. Tor/I2P exit node detection
        if ($this->checkTorExitNode($ip)) {
            $data['is_tor'] = true;
            $data['is_vpn'] = true;
            $data['provider'] = $data['provider'] ?? 'Tor Network';
            $data['sources'][] = 'tor_exit_list';
        }

        // 4. Residential proxy detection
        if ($this->checkResidentialProxy($ip, $request)) {
            $data['is_residential_proxy'] = true;
            $data['is_vpn'] = true;
            $data['sources'][] = 'residential_proxy';
        }

        // 5. Corporate VPN detection (whitelisted)
        if ($this->isCorporateVpn($ip, $asnData['asn'] ?? null)) {
            $data['is_corporate_vpn'] = true;
            $data['sources'][] = 'corporate_vpn_whitelist';
        }

        // 6. Behavioral anomaly check
        if ($user !== null && $this->hasBehavioralAnomaly($request, $user)) {
            $data['has_behavioral_anomaly'] = true;
            $data['sources'][] = 'behavioral_anomaly';
        }

        // 7. Geo-specific rules for Russian territories
        if ($this->isRussianTerritoryMismatch($data['country'] ?? null, $request, $user)) {
            $data['metadata']['russian_territory_mismatch'] = true;
            $data['sources'][] = 'geo_mismatch';
        }

        return $data;
    }

    /**
     * Check IP intelligence databases
     *
     * @param  string  $ip
     * @return array
     */
    private function checkIpIntelligence(string $ip): array
    {
        $result = [
            'country' => null,
            'city' => null,
            'isp' => null,
            'is_vpn' => false,
            'provider' => null,
            'sources' => [],
        ];

        $config = config('vpn-detection.providers', []);

        // MaxMind GeoIP2
        if ($config['maxmind']['enabled'] ?? false) {
            $maxmindData = $this->checkMaxMind($ip);
            if ($maxmindData !== null) {
                $result['country'] = $maxmindData['country'] ?? $result['country'];
                $result['city'] = $maxmindData['city'] ?? $result['city'];
                $result['isp'] = $maxmindData['isp'] ?? $result['isp'];
                if ($maxmindData['is_vpn'] ?? false) {
                    $result['is_vpn'] = true;
                    $result['provider'] = $maxmindData['provider'] ?? $result['provider'];
                }
                $result['sources'][] = 'maxmind';
            }
        }

        // IP2Location
        if ($config['ip2location']['enabled'] ?? false) {
            $ip2LocationData = $this->checkIp2Location($ip);
            if ($ip2LocationData !== null) {
                $result['country'] = $ip2LocationData['country'] ?? $result['country'];
                $result['city'] = $ip2LocationData['city'] ?? $result['city'];
                $result['isp'] = $ip2LocationData['isp'] ?? $result['isp'];
                if ($ip2LocationData['is_vpn'] ?? false) {
                    $result['is_vpn'] = true;
                    $result['provider'] = $ip2LocationData['provider'] ?? $result['provider'];
                }
                $result['sources'][] = 'ip2location';
            }
        }

        // AbuseIPDB
        if ($config['abuseipdb']['enabled'] ?? false) {
            $abuseData = $this->checkAbuseIPDB($ip);
            if ($abuseData !== null && ($abuseData['is_vpn'] ?? false)) {
                $result['is_vpn'] = true;
                $result['provider'] = $abuseData['provider'] ?? $result['provider'];
                $result['sources'][] = 'abuseipdb';
            }
        }

        // GetIPIntel
        if ($config['getipintel']['enabled'] ?? false) {
            $intelData = $this->checkGetIPIntel($ip);
            if ($intelData !== null && ($intelData['is_vpn'] ?? false)) {
                $result['is_vpn'] = true;
                $result['sources'][] = 'getipintel';
            }
        }

        return $result;
    }

    /**
     * Check ASN and hosting provider information
     *
     * @param  string  $ip
     * @return array
     */
    private function checkAsnAndHosting(string $ip): array
    {
        $result = [
            'asn' => null,
            'is_datacenter' => false,
            'is_vpn' => false,
            'provider' => null,
            'sources' => [],
        ];

        $config = config('vpn-detection.providers', []);
        $knownVpnAsns = $config['known_vpn_asns'] ?? [];

        // Get ASN information (simulated - in production use real ASN lookup)
        $asnInfo = $this->getAsnInfo($ip);
        if ($asnInfo !== null) {
            $result['asn'] = $asnInfo['asn'];
            $result['isp'] = $asnInfo['isp'] ?? $result['isp'];

            // Check if ASN is known VPN provider
            if (in_array($asnInfo['asn'], $knownVpnAsns, true)) {
                $result['is_vpn'] = true;
                $result['provider'] = $asnInfo['isp'] ?? 'Known VPN Provider';
                $result['sources'][] = 'asn_vpn_list';
            }

            // Check if datacenter/hosting
            if ($this->isDatacenterAsn($asnInfo['asn'], $asnInfo['isp'] ?? '')) {
                $result['is_datacenter'] = true;
                $result['sources'][] = 'datacenter_asn';
            }
        }

        return $result;
    }

    /**
     * Check if IP is a Tor exit node
     *
     * @param  string  $ip
     * @return bool
     */
    private function checkTorExitNode(string $ip): bool
    {
        $config = config('vpn-detection.providers', []);

        if (!($config['tor']['enabled'] ?? false)) {
            return false;
        }

        // Check Tor exit node list (cached)
        $torExitNodes = $this->cache->remember('tor_exit_nodes', 3600, function () {
            return $this->fetchTorExitNodes();
        });

        return in_array($ip, $torExitNodes, true);
    }

    /**
     * Check if IP is a residential proxy
     *
     * @param  string  $ip
     * @param  Request  $request
     * @return bool
     */
    private function checkResidentialProxy(string $ip, Request $request): bool
    {
        $config = config('vpn-detection.providers', []);

        if (!($config['residential_proxy']['enabled'] ?? false)) {
            return false;
        }

        // Check latency patterns (residential proxies often have high latency)
        $latencyCheck = $this->checkLatencyPatterns($ip, $request);
        if ($latencyCheck) {
            return true;
        }

        // Check known residential proxy ranges
        $knownRanges = $config['residential_proxy']['known_ranges'] ?? [];
        foreach ($knownRanges as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if IP is a corporate VPN (whitelisted)
     *
     * @param  string  $ip
     * @param  string|null  $asn
     * @return bool
     */
    private function isCorporateVpn(string $ip, ?string $asn): bool
    {
        $config = config('vpn-detection.corporate_vpn', []);
        $whitelist = $config['whitelist'] ?? [];

        // Check IP whitelist
        foreach ($whitelist['ips'] ?? [] as $whitelistedIp) {
            if ($this->ipInRange($ip, $whitelistedIp)) {
                return true;
            }
        }

        // Check ASN whitelist
        if ($asn !== null) {
            foreach ($whitelist['asns'] ?? [] as $whitelistedAsn) {
                if ($asn === $whitelistedAsn) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check for behavioral anomalies
     *
     * @param  Request  $request
     * @param  User  $user
     * @return bool
     */
    private function hasBehavioralAnomaly(Request $request, User $user): bool
    {
        try {
            $sessionId = session()->getId();
            $behavioralScore = $this->behavioralBiometrics->getSessionScore($user->id, $sessionId);
            
            if ($behavioralScore !== null && $behavioralScore < 0.75) {
                return true;
            }
            
            return false;
        } catch (\Throwable $e) {
            $this->log->channel('security')->error('Behavioral anomaly check failed', $this->masker->mask([
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]));
            return false;
        }
    }

    /**
     * Check for Russian territory geo mismatch
     *
     * @param  string|null  $country
     * @param  Request  $request
     * @param  User|null  $user
     * @return bool
     */
    private function isRussianTerritoryMismatch(?string $country, Request $request, ?User $user): bool
    {
        if ($country === null) {
            return false;
        }

        // Check if detected country is not Russia but user is from Russian territories
        if ($country !== 'RU' && $user !== null) {
            $userLocation = $user->location_country ?? null;
            if ($userLocation && in_array($userLocation, self::RUSSIAN_TERRITORIES, true)) {
                return true;
            }
        }

        // Check request headers for location hints
        $locationHeader = $request->header('X-User-Location');
        if ($locationHeader && in_array($locationHeader, self::RUSSIAN_TERRITORIES, true)) {
            return $country !== 'RU';
        }

        return false;
    }

    /**
     * Calculate risk level based on detection data
     *
     * @param  array  $data
     * @param  Request  $request
     * @param  User|null  $user
     * @return VpnRiskLevel
     */
    private function calculateRiskLevel(array $data, Request $request, ?User $user): VpnRiskLevel
    {
        // If no VPN detected, return LOW
        if (!($data['is_vpn'] ?? false)) {
            return VpnRiskLevel::LOW;
        }

        // CRITICAL: Tor + behavioral anomaly + new account/sensitive action
        if (($data['is_tor'] ?? false) && ($data['has_behavioral_anomaly'] ?? false)) {
            if ($this->isSensitiveAction($request, $user)) {
                return VpnRiskLevel::CRITICAL;
            }
        }

        // CRITICAL: Residential proxy + geo mismatch
        if (($data['is_residential_proxy'] ?? false) && ($data['metadata']['russian_territory_mismatch'] ?? false)) {
            return VpnRiskLevel::CRITICAL;
        }

        // HIGH: Tor or datacenter proxy
        if (($data['is_tor'] ?? false) || ($data['is_datacenter'] ?? false)) {
            return VpnRiskLevel::HIGH;
        }

        // HIGH: Behavioral anomaly + VPN
        if (($data['has_behavioral_anomaly'] ?? false)) {
            return VpnRiskLevel::HIGH;
        }

        // HIGH: Geo mismatch for Russian territories
        if ($data['metadata']['russian_territory_mismatch'] ?? false) {
            return VpnRiskLevel::HIGH;
        }

        // MEDIUM: Commercial VPN without additional red flags
        if (($data['is_vpn'] ?? false) && !($data['is_corporate_vpn'] ?? false)) {
            return VpnRiskLevel::MEDIUM;
        }

        // LOW: Corporate VPN (whitelisted)
        if ($data['is_corporate_vpn'] ?? false) {
            return VpnRiskLevel::LOW;
        }

        // Default to MEDIUM for unknown VPN
        return VpnRiskLevel::MEDIUM;
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
        $sensitiveRoutes = config('vpn-detection.sensitive_routes', []);

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
     * Get cached detection result
     *
     * @param  string  $ip
     * @return VpnDetectionResult|null
     */
    private function getCachedDetection(string $ip): ?VpnDetectionResult
    {
        $cacheKey = self::REDIS_KEY_PREFIX . $ip;
        $cached = $this->cache->get($cacheKey);

        if ($cached !== null) {
            return VpnDetectionResult::fromArray($cached);
        }

        return null;
    }

    /**
     * Cache detection result
     *
     * @param  string  $ip
     * @param  VpnDetectionResult  $result
     * @return void
     */
    private function cacheDetection(string $ip, VpnDetectionResult $result): void
    {
        $cacheKey = self::REDIS_KEY_PREFIX . $ip;
        $this->cache->put($cacheKey, $result->toArray(), self::CACHE_TTL_SECONDS);
    }

    /**
     * Log detection result
     *
     * @param  VpnDetectionResult  $result
     * @return void
     */
    private function logDetection(VpnDetectionResult $result): void
    {
        if ($result->isVpn) {
            $this->log->channel('security')->warning('VPN detected', $this->masker->mask([
                'ip_address' => $result->ipAddress,
                'provider' => $result->provider,
                'risk_level' => $result->riskLevel->value,
                'country' => $result->country,
                'sources' => $result->detectionSources,
                'is_tor' => $result->isTor,
                'is_datacenter' => $result->isDatacenter,
                'is_residential_proxy' => $result->isResidentialProxy,
                'is_corporate_vpn' => $result->isCorporateVpn,
            ]));
        } else {
            $this->log->channel('security')->debug('VPN check passed', $this->masker->mask([
                'ip_address' => $result->ipAddress,
            ]));
        }
    }

    /**
     * Check MaxMind GeoIP2 database (placeholder - implement with actual MaxMind SDK)
     *
     * @param  string  $ip
     * @return array|null
     */
    private function checkMaxMind(string $ip): ?array
    {
        // In production, implement actual MaxMind GeoIP2 database lookup
        // This is a placeholder for demonstration
        return null;
    }

    /**
     * Check IP2Location database (placeholder)
     *
     * @param  string  $ip
     * @return array|null
     */
    private function checkIp2Location(string $ip): ?array
    {
        // In production, implement actual IP2Location database lookup
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
        $config = config('vpn-detection.providers.abuseipdb', []);
        $apiKey = $config['api_key'] ?? null;

        if ($apiKey === null) {
            return null;
        }

        try {
            $response = $this->http->withHeaders([
                'Key' => $apiKey,
                'Accept' => 'application/json',
            ])->get("https://api.abuseipdb.com/api/v2/check", [
                'ipAddress' => $ip,
                'maxAgeInDays' => 90,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'is_vpn' => ($data['data']['isTor'] ?? false) || ($data['data']['abuseConfidenceScore'] ?? 0) > 50,
                    'provider' => $data['data']['isp'] ?? null,
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

    /**
     * Check GetIPIntel API (placeholder)
     *
     * @param  string  $ip
     * @return array|null
     */
    private function checkGetIPIntel(string $ip): ?array
    {
        $config = config('vpn-detection.providers.getipintel', []);
        $apiKey = $config['api_key'] ?? null;

        if ($apiKey === null) {
            return null;
        }

        try {
            $response = $this->http->get("http://check.getipintel.net/check.php", [
                'ip' => $ip,
                'contact' => $apiKey,
                'flags' => 'm',
            ]);

            if ($response->successful()) {
                $probability = (float) $response->body();
                return [
                    'is_vpn' => $probability > 0.8,
                ];
            }
        } catch (\Throwable $e) {
            $this->log->channel('security')->error('GetIPIntel check failed', [
                'ip' => $ip,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Get ASN information (placeholder)
     *
     * @param  string  $ip
     * @return array|null
     */
    private function getAsnInfo(string $ip): ?array
    {
        // In production, implement actual ASN lookup (e.g., using ipinfo.io, ipapi.co, etc.)
        // This is a placeholder for demonstration
        return null;
    }

    /**
     * Check if ASN is datacenter/hosting
     *
     * @param  string  $asn
     * @param  string  $isp
     * @return bool
     */
    private function isDatacenterAsn(string $asn, string $isp): bool
    {
        $config = config('vpn-detection.providers', []);
        $datacenterAsns = $config['datacenter_asns'] ?? [];
        $datacenterIsps = $config['datacenter_isps'] ?? [];

        if (in_array($asn, $datacenterAsns, true)) {
            return true;
        }

        foreach ($datacenterIsps as $pattern) {
            if (stripos($isp, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check latency patterns for residential proxy detection
     *
     * @param  string  $ip
     * @param  Request  $request
     * @return bool
     */
    private function checkLatencyPatterns(string $ip, Request $request): bool
    {
        // In production, implement actual latency measurement
        // This is a placeholder for demonstration
        return false;
    }

    /**
     * Fetch Tor exit nodes list
     *
     * @return array
     */
    private function fetchTorExitNodes(): array
    {
        try {
            $response = $this->http->get('https://check.torproject.org/torbulkexitlist');
            if ($response->successful()) {
                return array_filter(explode("\n", $response->body()));
            }
        } catch (\Throwable $e) {
            $this->log->channel('security')->error('Failed to fetch Tor exit nodes', $this->masker->mask([
                'error' => $e->getMessage(),
            ]));
        }

        return [];
    }
}
