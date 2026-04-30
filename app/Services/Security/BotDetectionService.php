<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\DTO\Security\BotDetectionResult;
use App\Enums\BotRiskLevel;
use App\Models\User;
use App\Services\Fraud\FraudControlService;
use Illuminate\Cache\CacheManager;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Carbon\CarbonImmutable;

/**
 * Bot Detection Service
 *
 * Multi-layered bot detection system for CatVRF.
 * Implements 4 layers of detection:
 * - Layer 0: Edge Protection (Cloudflare - external)
 * - Layer 1: Middleware checks (UA, headers, patterns)
 * - Layer 2: Behavioral Biometrics + AI Detection
 * - Layer 3: FraudControl + VpnDetection integration
 *
 * Production 2026 CANON:
 * - Gradient protection based on risk level
 * - Special rules for Russian territories
 * - Integration with existing security services
 * - Cache-aware for performance
 * - Fail-safe (never block legitimate users)
 *
 * @see https://github.com/nanolord126/CatVRF
 */
final readonly class BotDetectionService
{
    private const CACHE_TTL_SECONDS = 3600; // 1 hour
    private const REDIS_KEY_PREFIX = 'bot_detection:';

    // Russian territories requiring special handling
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
        private readonly BehavioralBiometricsService $behavioralBiometrics,
        private readonly VpnDetectionService $vpnDetection,
        private readonly FraudControlService $fraudControl,
        private readonly SecurityMonitoringService $securityMonitoring,
        private readonly ResidentialProxyDetectionService $residentialProxyDetection,
    ) {}

    /**
     * Detect bot traffic for a request
     *
     * @param  Request  $request
     * @param  User|null  $user
     * @return BotDetectionResult
     */
    public function detect(Request $request, ?User $user = null): BotDetectionResult
    {
        if (!config('bot-protection.enabled', true)) {
            return BotDetectionResult::clean($request);
        }

        $ip = $request->ip();
        $correlationId = Str::uuid()->toString();

        // Skip detection for private IPs
        if ($this->isPrivateIp($ip)) {
            return BotDetectionResult::clean($request);
        }

        // Check cache first
        $cachedResult = $this->getCachedDetection($ip);
        if ($cachedResult !== null) {
            return $cachedResult;
        }

        // Perform multi-layered detection
        $detectionData = $this->performDetection($request, $user, $correlationId);

        // Calculate risk level
        $riskLevel = $this->calculateRiskLevel($detectionData, $request, $user);

        // Calculate confidence
        $confidence = $this->calculateConfidence($detectionData, $riskLevel);

        // Create result
        $result = $riskLevel === BotRiskLevel::LOW
            ? BotDetectionResult::clean($request)
            : BotDetectionResult::detected(
                $request,
                $riskLevel,
                $confidence,
                $detectionData['signals'] ?? [],
                $detectionData['sources'] ?? [],
                $detectionData['matched_rule'] ?? null,
                $detectionData['metadata'] ?? []
            );

        // Cache the result
        $this->cacheDetection($ip, $result);

        // Log detection
        $this->logDetection($result, $correlationId, $user);

        return $result;
    }

    /**
     * Apply protection measures based on detection result
     *
     * @param  Request  $request
     * @param  BotDetectionResult  $result
     * @param  User|null  $user
     * @return void
     */
    public function applyProtection(Request $request, BotDetectionResult $result, ?User $user = null): void
    {
        if (!$result->requiresProtection()) {
            return;
        }

        $correlationId = Str::uuid()->toString();

        // Log to security monitoring
        $this->securityMonitoring->logEvent(
            'bot_detected',
            $user?->id ?? 0,
            [
                'risk_level' => $result->riskLevel->value,
                'confidence' => $result->confidence,
                'ip_address' => $result->ipAddress,
                'user_agent' => $result->userAgent,
                'detection_sources' => $result->detectionSources,
                'matched_rule' => $result->matchedRule,
            ],
            $correlationId,
            $result->riskLevel->getScore()
        );

        // Apply protection based on risk level
        match ($result->riskLevel) {
            BotRiskLevel::MEDIUM => $this->applyMediumProtection($request, $result, $user, $correlationId),
            BotRiskLevel::HIGH => $this->applyHighProtection($request, $result, $user, $correlationId),
            BotRiskLevel::CRITICAL => $this->applyCriticalProtection($request, $result, $user, $correlationId),
            default => null,
        };
    }

    /**
     * Perform multi-layered bot detection
     *
     * @param  Request  $request
     * @param  User|null  $user
     * @param  string  $correlationId
     * @return array
     */
    private function performDetection(Request $request, ?User $user, string $correlationId): array
    {
        $data = [
            'is_bot' => false,
            'signals' => [],
            'sources' => [],
            'matched_rule' => null,
            'metadata' => [],
        ];

        // Layer 1: User-Agent and header checks
        $uaCheck = $this->checkUserAgent($request);
        $data = array_merge($data, $uaCheck);

        // Layer 2: Headless browser detection
        if (config('bot-protection.headless_detection.enabled', true)) {
            $headlessCheck = $this->checkHeadlessBrowser($request);
            $data = array_merge($data, $headlessCheck);
        }

        // Layer 3: Behavioral biometrics
        if (config('bot-protection.layers.behavioral_analysis', true) && $user !== null) {
            $behavioralCheck = $this->checkBehavioralPatterns($request, $user);
            $data = array_merge($data, $behavioralCheck);
        }

        // Layer 4: VPN detection integration
        if (config('bot-protection.layers.vpn_detection', true)) {
            $vpnCheck = $this->checkVpnIntegration($request, $user);
            $data = array_merge($data, $vpnCheck);
        }
4.5: Residential proxy detection integration
        if (config('proxy-detection.enabled', true) && config('bot-protection.layers.residential_proxy', true)) {
            $residentialProxyCheck = $this->checkResidentialProxyIntegration($request, $user);
            $data = array_merge($data, $residentialProxyCheck);
        }

        // Layer 
        // Layer 5: Fraud control integration
        if (config('bot-protection.layers.fraud_integration', true)) {
            $fraudCheck = $this->checkFraudIntegration($request, $user);
            $data = array_merge($data, $fraudCheck);
        }

        // Layer 6: Velocity checks
        $velocityCheck = $this->checkVelocity($request, $user);
        $data = array_merge($data, $velocityCheck);

        // Layer 7: Russian territories special rules
        if (config('bot-protection.russian_territories.enabled', true)) {
            $territoryCheck = $this->checkRussianTerritories($request, $user, $data);
            $data = array_merge($data, $territoryCheck);
        }

        return $data;
    }

    /**
     * Check User-Agent against whitelist/blacklist
     *
     * @param  Request  $request
     * @return array
     */
    private function checkUserAgent(Request $request): array
    {
        $userAgent = $request->userAgent();
        $result = [
            'is_bot' => false,
            'signals' => [],
            'sources' => [],
            'matched_rule' => null,
        ];

        if ($userAgent === null) {
            $result['is_bot'] = true;
            $result['signals'][] = 'missing_user_agent';
            $result['sources'][] = 'user_agent_check';
            $result['matched_rule'] = 'missing_ua';
            return $result;
        }

        // Check whitelist (known good bots)
        $whitelist = config('bot-protection.whitelist.user_agents', []);
        foreach ($whitelist as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                $result['signals'][] = 'whitelisted_bot';
                $result['sources'][] = 'whitelist';
                $result['metadata']['whitelisted_as'] = $pattern;
                return $result;
            }
        }

        // Check blacklist (known bad bots)
        $blacklist = config('bot-protection.blacklist.user_agents', []);
        foreach ($blacklist as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                $result['is_bot'] = true;
                $result['signals'][] = 'blacklisted_bot';
                $result['sources'][] = 'blacklist';
                $result['matched_rule'] = "blacklisted_{$pattern}";
                return $result;
            }
        }

        // Check patterns
        $patterns = config('bot-protection.blacklist.patterns', []);
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                $result['is_bot'] = true;
                $result['signals'][] = 'pattern_match';
                $result['sources'][] = 'pattern_check';
                $result['matched_rule'] = $pattern;
                return $result;
            }
        }

        return $result;
    }

    /**
     * Check for headless browser patterns
     *
     * @param  Request  $request
     * @return array
     */
    private function checkHeadlessBrowser(Request $request): array
    {
        $result = [
            'is_bot' => false,
            'signals' => [],
            'sources' => [],
        ];

        $userAgent = $request->userAgent();
        if ($userAgent === null) {
            return $result;
        }

        $patterns = config('bot-protection.headless_detection.user_agent_patterns', []);
        foreach ($patterns as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                $result['is_bot'] = true;
                $result['signals'][] = 'headless_browser';
                $result['sources'][] = 'headless_detection';
                $result['matched_rule'] = "headless_{$pattern}";
                return $result;
            }
        }

        // Check for suspicious headers
        $suspiciousHeaders = [
            'X-Headless-Chrome',
            'X-PhantomJS',
            'X-Selenium',
        ];

        foreach ($suspiciousHeaders as $header) {
            if ($request->hasHeader($header)) {
                $result['is_bot'] = true;
                $result['signals'][] = 'headless_header';
                $result['sources'][] = 'header_check';
                $result['matched_rule'] = "header_{$header}";
                return $result;
            }
        }

        return $result;
    }

    /**
     * Check behavioral patterns via BehavioralBiometricsService
     *
     * @param  Request  $request
     * @param  User  $user
     * @return array
     */
    private function checkBehavioralPatterns(Request $request, User $user): array
    {
        $result = [
            'is_bot' => false,
            'signals' => [],
            'sources' => [],
            'metadata' => [],
        ];

        try {
            // Get behavioral signals from request (if available)
            $signals = $request->input('behavioral_signals', []);
            
            if (!empty($signals)) {
                $analysis = $this->behavioralBiometrics->analyzeSignals(
                    $user,
                    $signals,
                    $request->session()->getId()
                );

                $result['metadata']['behavioral_score'] = $analysis['overall_score'] ?? 0.5;
                $result['metadata']['is_anomalous'] = $analysis['is_anomalous'] ?? false;

                // Check for bot-like patterns
                if ($this->hasBotLikeBehavior($analysis)) {
                    $result['is_bot'] = true;
                    $result['signals'][] = 'behavioral_anomaly';
                    $result['sources'][] = 'behavioral_biometrics';
                    $result['matched_rule'] = 'behavioral_bot_patterns';
                }
            }
        } catch (\Throwable $e) {
            $this->log->channel('security')->warning('Behavioral check failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $result;
    }

    /**
     * Check if behavioral analysis indicates bot-like behavior
     *
     * @param  array  $analysis
     * @return bool
     */
    private function hasBotLikeBehavior(array $analysis): bool
    {
        $config = config('bot-protection.behavioral_detection', []);

        // Too perfect typing (no variance)
        $typingScore = $analysis['typing_score'] ?? 0.5;
        if ($typingScore > 0.95) {
            return true;
        }

        // Too fast mouse movement
        $mouseScore = $analysis['mouse_score'] ?? 0.5;
        if ($mouseScore > 0.95) {
            return true;
        }

        // Overall anomaly
        if ($analysis['is_anomalous'] ?? false) {
            $severity = $analysis['anomaly_severity'] ?? 'none';
            if (in_array($severity, ['high', 'critical'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check VPN detection integration
     *
     * @param  Request  $request
     * @param  User|null  $user
     * @return array
     */
    private function checkVpnIntegration(Request $request, ?User $user): array
    {
        $result = [
            'is_bot' => false,
            'signals' => [],
            'sources' => [],
            'metadata' => [],
        ];

        try {
            $vpnResult = $this->vpnDetection->detect($request, $user);

            $result['metadata']['vpn_detected'] = $vpnResult->isVpn;
            $result['metadata']['vpn_risk_level'] = $vpnResult->riskLevel->value;
            $result['metadata']['is_tor'] = $vpnResult->isTor;
            $result['metadata']['is_datacenter'] = $vpnResult->isDatacenter;

            // VPN + suspicious behavior = higher risk
            if ($vpnResult->isVpn && !$vpnResult->isCorporateVpn) {
                $result['signals'][] = 'vpn_detected';
                $result['sources'][] = 'vpn_detection';

                if (in_array($vpnResult->riskLevel->value, ['high', 'critical'], true)) {
                    $result['is_bot'] = true;
                    $result['matched_rule'] = 'vpn_high_risk';
                }
            }
        } catch (\Throwable $e) {
            $this->log->channel('security')->warning('VPN check failed', [
                'error' => $e->getMessage(),
            ]residential proxy detection integration
     *
     * @param  Request  $request
     * @param  User|null  $user
     * @return array
     */
    private function checkResidentialProxyIntegration(Request $request, ?User $user): array
    {
        $result = [
            'is_bot' => false,
            'signals' => [],
            'sources' => [],
            'metadata' => [],
        ];

        try {
            $residentialProxyResult = $this->residentialProxyDetection->detect($request, $user);

            $result['metadata']['residential_proxy_detected'] = $residentialProxyResult->isResidentialProxy;
            $result['metadata']['residential_proxy_risk_level'] = $residentialProxyResult->riskLevel->value;
            $result['metadata']['residential_proxy_provider'] = $residentialProxyResult->proxyProvider->value;
            $result['metadata']['residential_proxy_confidence'] = $residentialProxyResult->confidenceScore;
            $result['metadata']['residential_proxy_type'] = $residentialProxyResult->proxyType->value;

            // Residential proxy + suspicious behavior = higher risk
            if ($residentialProxyResult->isResidentialProxy) {
                $result['signals'][] = 'residential_proxy';
                $result['sources'][] = 'residential_proxy_detection';

                // High confidence residential proxy = likely bot
                if ($residentialProxyResult->confidenceScore >= 70) {
                    $result['is_bot'] = true;
                    $result['matched_rule'] = 'residential_proxy_high_confidence';
                }

                // Residential proxy from high-risk provider
                if ($residentialProxyResult->proxyProvider->isHighRisk()) {
                    $result['is_bot'] = true;
                    $result['matched_rule'] = 'residential_proxy_high_risk_provider';
                }

                // Residential proxy + behavioral anomaly = critical
                if ($residentialProxyResult->hasBehavioralAnomaly) {
                    $result['is_bot'] = true;
                    $result['matched_rule'] = 'residential_proxy_behavioral_anomaly';
                }
            }
        } catch (\Throwable $e) {
            $this->log->channel('security')->warning('Residential proxy check failed', [
                'error' => $e->getMessage(),
            ]);
        }

        return $result;
    }

    /**
     * Check );
        }

        return $result;
    }

    /**
     * Check fraud control integration
     *
     * @param  Request  $request
     * @param  User|null  $user
     * @return array
     */
    private function checkFraudIntegration(Request $request, ?User $user): array
    {
        $result = [
            'is_bot' => false,
            'signals' => [],
            'sources' => [],
            'metadata' => [],
        ];

        try {
            $context = [
                'user_id' => $user?->id,
                'tenant_id' => $user?->tenant_id,
                'ip_address' => $request->ip(),
                'action' => $request->route()?->getName() ?? 'unknown',
            ];

            $fraudCheck = $this->fraudControl->checkRequest($context);

            $result['metadata']['fraud_score'] = $fraudCheck['fraud_score'] ?? 0.0;
            $result['metadata']['fraud_indicators'] = $fraudCheck['indicators'] ?? [];

            if ($fraudCheck['should_block'] ?? false) {
                $result['is_bot'] = true;
                $result['signals'][] = 'fraud_detected';
                $result['sources'][] = 'fraud_control';
                $result['matched_rule'] = 'fraud_high_score';
            }
        } catch (\Throwable $e) {
            $this->log->channel('security')->warning('Fraud check failed', [
                'error' => $e->getMessage(),
            ]);
        }

        return $result;
    }

    /**
     * Check request velocity
     *
     * @param  Request  $request
     * @param  User|null  $user
     * @return array
     */
    private function checkVelocity(Request $request, ?User $user): array
    {
        $result = [
            'is_bot' => false,
            'signals' => [],
            'sources' => [],
        ];

        $ip = $request->ip();
        $route = $request->route()?->getName() ?? 'unknown';

        try {
            $velocityCheck = $this->fraudControl->checkVelocity($user?->id, $ip, $route);

            if ($velocityCheck['is_high_velocity'] ?? false) {
                $result['is_bot'] = true;
                $result['signals'][] = 'high_velocity';
                $result['sources'][] = 'velocity_check';
                $result['matched_rule'] = 'velocity_exceeded';
                $result['metadata']['requests'] = $velocityCheck['requests'] ?? 0;
            }
        } catch (\Throwable $e) {
            $this->log->channel('security')->warning('Velocity check failed', [
                'error' => $e->getMessage(),
            ]);
        }

        return $result;
    }

    /**
     * Check Russian territories special rules
     *
     * @param  Request  $request
     * @param  User|null  $user
     * @param  array  $detectionData
     * @return array
     */
    private function checkRussianTerritories(Request $request, ?User $user, array $detectionData): array
    {
        $result = [
            'is_bot' => false,
            'signals' => [],
            'sources' => [],
            'metadata' => [],
        ];

        $config = config('bot-protection.russian_territories', []);
        if (!$config['enabled']) {
            return $result;
        }

        // Check if VPN detected + Russian territory
        $vpnDetected = $detectionData['metadata']['vpn_detected'] ?? false;
        $vpnRiskLevel = $detectionData['metadata']['vpn_risk_level'] ?? 'low';

        if ($vpnDetected && $config['vpn_auto_high_risk']) {
            // Check if user is from Russian territories
            if ($user !== null && $this->isFromRussianTerritory($user)) {
                $result['is_bot'] = true;
                $result['signals'][] = 'vpn_russian_territory';
                $result['sources'][] = 'russian_territory_rules';
                $result['matched_rule'] = 'vpn_russian_territory_high_risk';
                $result['metadata']['russian_territory'] = $user->location_country ?? null;
            }
        }

        // Check for scraping patterns from Russian territories
        $isHighVelocity = in_array('high_velocity', $detectionData['signals'] ?? [], true);
        if ($isHighVelocity && $config['scraping_critical_risk']) {
            if ($user !== null && $this->isFromRussianTerritory($user)) {
                $result['is_bot'] = true;
                $result['signals'][] = 'scraping_russian_territory';
                $result['sources'][] = 'russian_territory_rules';
                $result['matched_rule'] = 'scraping_russian_territory_critical';
            }
        }

        return $result;
    }

    /**
     * Check if user is from Russian territory
     *
     * @param  User  $user
     * @return bool
     */
    private function isFromRussianTerritory(User $user): bool
    {
        $location = $user->location_country ?? null;
        
        if ($location === null) {
            return false;
        }

        return in_array($location, self::RUSSIAN_TERRITORIES, true);
    }

    /**
     * Calculate risk level based on detection data
     *
     * @param  array  $data
     * @param  Request  $request
     * @param  User|null  $user
     * @return BotRiskLevel
     */
    private function calculateRiskLevel(array $data, Request $request, ?User $user): BotRiskLevel
    {
        // If not detected as bot, return LOW
        if (!($data['is_bot'] ?? false)) {
            return BotRiskLevel::LOW;
        }

        $signals = $data['signals'] ?? [];
        $sources = $data['sources'] ?? [];
        $metadata = $data['metadata'] ?? [];

        $thresholds = config('bot-protection.thresholds', [
            'medium' => 0.4,
            'high' => 0.65,
            'critical' => 0.85,
        ]);

        // Calculate raw score
        $score = 0.0;

        // Blacklisted bot = CRITICAL
        if (in_array('blacklisted_bot', $signals, true)) {
            return BotRiskLevel::CRITICAL;
        }

        // Headless browser + high velocity = CRITICAL
        if (in_array('headless_browser', $signals, true) && in_array('high_velocity', $signals, true)) {
            return BotRiskLevel::CRITICAL;
        }

        // VPN + Russian territory = CRITICAL
        if (in_array('vpn_russian_territory', $signals, true)) {
            return BotRiskLevel::CRITICAL;
        }

        // Scraping + Russian territory = CRITICAL
        if (in_array('scraping_russian_territory', $signals, true)) {
            return BotRiskLevel::CRITICAL;
        }

        // Fraud detected with high score = CRITICAL
        if (in_array('fraud_detected', $signals, true) && ($metadata['fraud_score'] ?? 0) >= 0.85) {
            return BotRiskLevel::CRITICAL;
        }

        // Tor/VPN + behavioral anomaly = HIGH
        if (in_array('vpn_detected', $signals, true) && in_array('behavioral_anomaly', $signals, true)) {
            return BotRiskLevel::HIGH;
        }

        // Residential proxy + behavioral anomaly = HIGH
        if (in_array('residential_proxy', $signals, true) && in_array('behavioral_anomaly', $signals, true)) {
            return BotRiskLevel::HIGH;
        }

        // High confidence residential proxy = HIGH
        if (in_array('residential_proxy', $signals, true) && ($metadata['residential_proxy_confidence'] ?? 0) >= 70) {
            return BotRiskLevel::HIGH;
        }

        // High velocity + suspicious UA = HIGH
        if (in_array('high_velocity', $signals, true) && in_array('headless_browser', $signals, true)) {
            return BotRiskLevel::HIGH;
        }

        // Behavioral anomaly = HIGH
        if (in_array('behavioral_anomaly', $signals, true)) {
            return BotRiskLevel::HIGH;
        }

        // Fraud detected = HIGH
        if (in_array('fraud_detected', $signals, true)) {
            return BotRiskLevel::HIGH;
        }

        // VPN detected = MEDIUM
        if (in_array('vpn_detected', $signals, true)) {
            return BotRiskLevel::MEDIUM;
        }

        // Headless browser = MEDIUM
        if (in_array('headless_browser', $signals, true)) {
            return BotRiskLevel::MEDIUM;
        }

        // High velocity = MEDIUM
        if (in_array('high_velocity', $signals, true)) {
            return BotRiskLevel::MEDIUM;
        }

        // Default to MEDIUM
        return BotRiskLevel::MEDIUM;
    }

    /**
     * Calculate confidence score
     *
     * @param  array  $data
     * @param  BotRiskLevel  $riskLevel
     * @return float
     */
    private function calculateConfidence(array $data, BotRiskLevel $riskLevel): float
    {
        $sources = $data['sources'] ?? [];
        $sourceCount = count($sources);

        // More sources = higher confidence
        $baseConfidence = min(0.5 + ($sourceCount * 0.1), 1.0);

        // Adjust based on risk level
        return match ($riskLevel) {
            BotRiskLevel::CRITICAL => min($baseConfidence + 0.2, 1.0),
            BotRiskLevel::HIGH => min($baseConfidence + 0.1, 1.0),
            BotRiskLevel::MEDIUM => $baseConfidence,
            BotRiskLevel::LOW => 0.0,
        };
    }

    /**
     * Apply Medium risk protection (Challenge + Rate Limit)
     *
     * @param  Request  $request
     * @param  BotDetectionResult  $result
     * @param  User|null  $user
     * @param  string  $correlationId
     * @return void
     */
    private function applyMediumProtection(Request $request, BotDetectionResult $result, ?User $user, string $correlationId): void
    {
        $this->log->channel('security')->warning('Bot detected - Medium risk', [
            'ip_address' => $result->ipAddress,
            'user_agent' => $result->userAgent,
            'risk_level' => $result->riskLevel->value,
            'correlation_id' => $correlationId,
        ]);

        // Rate limit the IP
        if (config('bot-protection.rate_limiting.enabled', true)) {
            $this->applyRateLimit($request, 'medium');
        }

        // Challenge will be handled by middleware
    }

    /**
     * Apply High risk protection (Block + Cooldown)
     *
     * @param  Request  $request
     * @param  BotDetectionResult  $result
     * @param  User|null  $user
     * @param  string  $correlationId
     * @return void
     */
    private function applyHighProtection(Request $request, BotDetectionResult $result, ?User $user, string $correlationId): void
    {
        $this->log->channel('security')->error('Bot detected - High risk', [
            'ip_address' => $result->ipAddress,
            'user_agent' => $result->userAgent,
            'risk_level' => $result->riskLevel->value,
            'correlation_id' => $correlationId,
            'user_id' => $user?->id,
        ]);

        // Apply cooldown if user exists
        if ($user !== null) {
            $cooldownHours = config('bot-protection.protection.high.cooldown_hours', 24);
            // This would integrate with CooldownService
            // For now, just log
        }

        // Notify owners
        if (config('bot-protection.notifications.enabled', true)) {
            // Notification logic would go here
        }
    }

    /**
     * Apply Critical risk protection (Permanent block + Full cooldown)
     *
     * @param  Request  $request
     * @param  BotDetectionResult  $result
     * @param  User|null  $user
     * @param  string  $correlationId
     * @return void
     */
    private function applyCriticalProtection(Request $request, BotDetectionResult $result, ?User $user, string $correlationId): void
    {
        $this->log->channel('security')->critical('Bot detected - Critical risk', [
            'ip_address' => $result->ipAddress,
            'user_agent' => $result->userAgent,
            'risk_level' => $result->riskLevel->value,
            'correlation_id' => $correlationId,
            'user_id' => $user?->id,
            'matched_rule' => $result->matchedRule,
        ]);

        // Apply extended cooldown
        if ($user !== null) {
            $cooldownHours = config('bot-protection.protection.critical.cooldown_hours', 168);
            // This would integrate with CooldownService
        }

        // Invalidate split key
        if (config('bot-protection.protection.critical.invalidate_split_key', true) && $user !== null) {
            // Split key invalidation logic would go here
        }

        // Notify all owners and super-admins
        if (config('bot-protection.notifications.enabled', true)) {
            // Critical notification logic would go here
        }
    }

    /**
     * Apply rate limiting to IP
     *
     * @param  Request  $request
     * @param  string  $riskLevel
     * @return void
     */
    private function applyRateLimit(Request $request, string $riskLevel): void
    {
        $config = config("bot-protection.rate_limiting.by_ip.{$riskLevel}", []);
        if (empty($config)) {
            return;
        }

        $key = "bot_rate_limit:{$request->ip()}:{$riskLevel}";
        $maxAttempts = $config['max_attempts'] ?? 10;
        $decayMinutes = $config['decay_minutes'] ?? 5;

        $attempts = $this->cache->get($key, 0);

        if ($attempts >= $maxAttempts) {
            $this->log->channel('security')->warning('Bot rate limit exceeded', [
                'ip_address' => $request->ip(),
                'risk_level' => $riskLevel,
                'attempts' => $attempts,
            ]);
        }

        $this->cache->increment($key, 1, $decayMinutes * 60);
    }

    /**
     * Check if IP is private
     *
     * @param  string  $ip
     * @return bool
     */
    private function isPrivateIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }

    /**
     * Get cached detection result
     *
     * @param  string  $ip
     * @return BotDetectionResult|null
     */
    private function getCachedDetection(string $ip): ?BotDetectionResult
    {
        if (!config('bot-protection.cache.enabled', true)) {
            return null;
        }

        $cacheKey = self::REDIS_KEY_PREFIX . $ip;
        $cached = $this->cache->get($cacheKey);

        if ($cached !== null) {
            return BotDetectionResult::fromArray($cached);
        }

        return null;
    }

    /**
     * Cache detection result
     *
     * @param  string  $ip
     * @param  BotDetectionResult  $result
     * @return void
     */
    private function cacheDetection(string $ip, BotDetectionResult $result): void
    {
        if (!config('bot-protection.cache.enabled', true)) {
            return;
        }

        $cacheKey = self::REDIS_KEY_PREFIX . $ip;
        $ttl = config('bot-protection.cache.ttl_seconds', self::CACHE_TTL_SECONDS);

        $this->cache->put($cacheKey, $result->toArray(), $ttl);
    }

    /**
     * Log detection result
     *
     * @param  BotDetectionResult  $result
     * @param  string  $correlationId
     * @param  User|null  $user
     * @return void
     */
    private function logDetection(BotDetectionResult $result, string $correlationId, ?User $user): void
    {
        $logConfig = config('bot-protection.logging', []);

        if (!$logConfig['log_all_detections'] && !$result->isBot) {
            return;
        }

        if ($logConfig['log_only_risky'] && !$result->requiresProtection()) {
            return;
        }

        $this->log->channel($logConfig['channel'] ?? 'security')->info('Bot detection result', [
            'is_bot' => $result->isBot,
            'risk_level' => $result->riskLevel->value,
            'confidence' => $result->confidence,
            'ip_address' => $logConfig['anonymize_ip'] ? $this->anonymizeIp($result->ipAddress) : $result->ipAddress,
            'user_agent' => $result->userAgent,
            'detection_sources' => $result->detectionSources,
            'matched_rule' => $result->matchedRule,
            'user_id' => $user?->id,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Anonymize IP address (mask last octet)
     *
     * @param  string  $ip
     * @return string
     */
    private function anonymizeIp(string $ip): string
    {
        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            $parts[3] = '0';
            return implode('.', $parts);
        }

        return $ip;
    }
}
