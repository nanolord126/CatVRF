<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Models\FstecThreat;
use App\Services\Fraud\FraudControlService;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Log\LogManager;

/**
 * Threat Model Service
 * 
 * Управление моделью угроз CatVRF с учётом угроз ИИ из БДУ ФСТЭК.
 * Обеспечивает автоматическую реакцию на новые угрозы, обновление risk scores
 * в FraudControl и InsiderThreatService, и генерацию рекомендаций по mitigations.
 * 
 * Reference: https://bdu.fstec.ru/ section "Угрозы безопасности информации систем искусственного интеллекта"
 * Methodology: Методика ФСТЭК 2021 + Приказ №21
 */
final class ThreatModelService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const RISK_ADJUSTMENT_KEY = 'threat_model_risk_adjustment';
    
    // Quantum risk levels
    private const QUANTUM_RISK_LOW = 0;
    private const QUANTUM_RISK_MEDIUM = 1;
    private const QUANTUM_RISK_HIGH = 2;
    private const QUANTUM_RISK_CRITICAL = 3;

    public function __construct(
        private readonly CacheManager $cache,
        private readonly LogManager $log,
        private readonly EventDispatcher $eventDispatcher,
        private readonly FstecBduService $fstecBduService,
        private readonly ?FraudControlService $fraudControl = null,
    ) {}

    /**
     * Analyze current threat landscape and update risk scores
     * 
     * @return array Analysis results
     */
    public function analyzeThreatLandscape(): array
    {
        $results = [
            'total_threats' => 0,
            'ai_threats' => 0,
            'critical_threats' => 0,
            'unmitigated_critical' => 0,
            'risk_adjustment' => 0.0,
            'recommendations' => [],
        ];

        // Get all relevant threats
        $threats = $this->fstecBduService->getRelevantThreats();
        $results['total_threats'] = $threats->count();

        // Get AI-specific threats
        $aiThreats = $this->fstecBduService->getAIThreats();
        $results['ai_threats'] = $aiThreats->count();

        // Count critical threats
        $criticalThreats = $threats->where('risk_level', 'critical');
        $results['critical_threats'] = $criticalThreats->count();

        // Count unmitigated critical threats
        $unmitigatedCritical = $criticalThreats->where('is_mitigated', false);
        $results['unmitigated_critical'] = $unmitigatedCritical->count();

        // Calculate global risk adjustment
        $results['risk_adjustment'] = $this->calculateGlobalRiskAdjustment($threats, $aiThreats);

        // Generate recommendations
        $results['recommendations'] = $this->generateRecommendations($unmitigatedCritical, $aiThreats);

        // Cache the results
        $this->cache->put('threat_landscape_analysis', $results, self::CACHE_TTL);

        // Log the analysis
        $this->log->info('Threat landscape analysis completed', $results);

        return $results;
    }

    /**
     * React to new AI threats from BDU
     * 
     * @return array Reaction results
     */
    public function reactToNewAIThreats(): array
    {
        if (! $this->fstecBduService->hasNewAIThreats()) {
            return [
                'status' => 'no_new_threats',
                'message' => 'No new AI threats detected',
            ];
        }

        $criticalAIThreats = $this->fstecBduService->getCriticalAIThreats();
        
        $results = [
            'status' => 'new_threats_detected',
            'critical_count' => $criticalAIThreats->count(),
            'actions_taken' => [],
        ];

        foreach ($criticalAIThreats as $threat) {
            $action = $this->reactToAIThreat($threat);
            $results['actions_taken'][] = $action;
        }

        // Update global risk scores
        $this->updateGlobalRiskScores($criticalAIThreats);

        // Dispatch event for notification
        $this->eventDispatcher->dispatch(new \App\Events\Security\NewAIThreatDetected(
            threats: $criticalAIThreats,
            actions: $results['actions_taken'],
        ));

        $this->log->warning('New critical AI threats detected and reacted', $results);

        return $results;
    }

    /**
     * React to a specific AI threat
     * 
     * @param  FstecThreat  $threat  AI threat
     * @return array Action taken
     */
    private function reactToAIThreat(FstecThreat $threat): array
    {
        $action = [
            'threat_id' => $threat->fstec_id,
            'threat_name' => $threat->name,
            'risk_level' => $threat->risk_level,
            'affected_systems' => $threat->affected_systems,
            'actions' => [],
        ];

        // Increase risk score for affected systems
        foreach ($threat->affected_systems as $system) {
            $adjustment = $this->fstecBduService->getAIThreatRiskAdjustment($system);
            $this->cache->put("ai_risk_adjustment:{$system}", $adjustment, self::CACHE_TTL);
            $action['actions'][] = "Increased risk score for {$system} by {$adjustment}";
        }

        // Add recommendation to queue for review
        $this->cache->put("threat_recommendation:{$threat->fstec_id}", [
            'threat' => $threat->toArray(),
            'recommendation' => $this->fstecBduService->getAIThreatRecommendations(
                $threat->affected_systems[0] ?? 'general'
            ),
        ], self::CACHE_TTL * 24); // 24 hours

        $action['actions'][] = 'Added recommendation for review';

        return $action;
    }

    /**
     * Update global risk scores in FraudControl and InsiderThreatService
     * 
     * @param  \Illuminate\Database\Eloquent\Collection  $threats  Threats
     * @return void
     */
    private function updateGlobalRiskScores(\Illuminate\Database\Eloquent\Collection $threats): void
    {
        $totalAdjustment = 0.0;

        foreach ($threats as $threat) {
            $riskScore = $threat->calculateRiskScore();
            $totalAdjustment += ($riskScore / 10) * 0.1; // 10% per critical threat
        }

        $this->cache->put(self::RISK_ADJUSTMENT_KEY, min(0.5, $totalAdjustment), self::CACHE_TTL);

        $this->log->info('Global risk scores updated due to AI threats', [
            'adjustment' => $totalAdjustment,
            'threat_count' => $threats->count(),
        ]);
    }

    /**
     * Calculate global risk adjustment
     * 
     * @param  \Illuminate\Database\Eloquent\Collection  $threats  All threats
     * @param  \Illuminate\Database\Eloquent\Collection  $aiThreats  AI threats
     * @return float Risk adjustment (0-1)
     */
    private function calculateGlobalRiskAdjustment(
        \Illuminate\Database\Eloquent\Collection $threats,
        \Illuminate\Database\Eloquent\Collection $aiThreats
    ): float {
        $adjustment = 0.0;

        // Base adjustment from critical threats
        $criticalCount = $threats->where('risk_level', 'critical')->count();
        $adjustment += min(0.3, $criticalCount * 0.05);

        // Additional adjustment from AI threats (higher weight)
        $aiCriticalCount = $aiThreats->where('risk_level', 'critical')->count();
        $adjustment += min(0.4, $aiCriticalCount * 0.08);

        // Additional adjustment from unmitigated threats
        $unmitigatedCount = $threats->where('is_mitigated', false)->where('risk_level', 'critical')->count();
        $adjustment += min(0.3, $unmitigatedCount * 0.06);

        return min(1.0, $adjustment);
    }

    /**
     * Generate recommendations based on threats
     * 
     * @param  \Illuminate\Database\Eloquent\Collection  $criticalThreats  Critical threats
     * @param  \Illuminate\Database\Eloquent\Collection  $aiThreats  AI threats
     * @param  \Illuminate\Database\Eloquent\Collection  $quantumThreats  Quantum threats
     * @return array Recommendations
     */
    private function generateRecommendations(
        \Illuminate\Database\Eloquent\Collection $criticalThreats,
        \Illuminate\Database\Eloquent\Collection $aiThreats
    ): array {
        $recommendations = [];

        // AI-specific recommendations
        if ($aiThreats->isNotEmpty()) {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'AI Security',
                'message' => sprintf(
                    'Detected %d AI-related threats. Review and implement mitigations per Приказ ФСТЭК №21.',
                    $aiThreats->count()
                ),
                'threat_ids' => $aiThreats->pluck('fstec_id')->toArray(),
            ];
        }

        // Adversarial attack recommendations
        $adversarialThreats = $aiThreats->filter(fn ($t) => str_contains($t->name, 'Adversarial'));
        if ($adversarialThreats->isNotEmpty()) {
            $recommendations[] = [
                'priority' => 'critical',
                'category' => 'Adversarial Defense',
                'message' => 'Adversarial attacks detected. Implement ensemble models and adversarial training for behavioral biometrics and liveness detection.',
                'threat_ids' => $adversarialThreats->pluck('fstec_id')->toArray(),
                'affected_systems' => ['kyb_liveness', 'behavioral_biometrics', 'deepfake_detection'],
            ];
        }

        // Prompt injection recommendations
        $promptInjectionThreats = $aiThreats->filter(fn ($t) => str_contains($t->name, 'Prompt'));
        if ($promptInjectionThreats->isNotEmpty()) {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'Prompt Security',
                'message' => 'Prompt injection threats detected. Implement guardrails, input validation, and output sanitization for all LLM calls.',
                'threat_ids' => $promptInjectionThreats->pluck('fstec_id')->toArray(),
                'affected_systems' => ['ai_moderation', 'ai_recommendations', 'ai_diagnostics'],
            ];
        }

        // Data poisoning recommendations
        $poisoningThreats = $aiThreats->filter(fn ($t) => str_contains($t->name, 'Poisoning'));
        if ($poisoningThreats->isNotEmpty()) {
            $recommendations[] = [
                'priority' => 'critical',
                'category' => 'Data Integrity',
                'message' => 'Data poisoning threats detected. Implement dataset validation, differential privacy, and regular audit of training data.',
                'threat_ids' => $poisoningThreats->pluck('fstec_id')->toArray(),
                'affected_systems' => ['behavioral_biometrics', 'fraud_ml', 'insider_threat_ml'],
            ];
        }

        return $recommendations;
    }

    /**
     * Get current risk adjustment for FraudControl
     * 
     * @return float Risk adjustment (0-1)
     */
    public function getCurrentRiskAdjustment(): float
    {
        return $this->cache->get(self::RISK_ADJUSTMENT_KEY, 0.0);
    }

    /**
     * Get risk adjustment for specific AI system
     * 
     * @param  string  $aiSystem  AI system name
     * @return float Risk adjustment (0-0.5)
     */
    public function getAISystemRiskAdjustment(string $aiSystem): float
    {
        return $this->cache->get("ai_risk_adjustment:{$aiSystem}", 0.0);
    }

    /**
     * Get threat model report for compliance
     * 
     * @return array Report data
     */
    public function getComplianceReport(): array
    {
        $threats = $this->fstecBduService->getRelevantThreats();
        $aiThreats = $this->fstecBduService->getAIThreats();
        $quantumThreats = $this->getQuantumThreats();

        return [
            'report_date' => now()->toIso8601String(),
            'bdu_sync_date' => FstecThreat::max('synced_at')?->toIso8601String(),
            'total_threats_monitored' => $threats->count(),
            'ai_threats_monitored' => $aiThreats->count(),
            'critical_threats' => $threats->where('risk_level', 'critical')->count(),
            'unmitigated_threats' => $threats->where('is_mitigated', false)->count(),
            'ai_threats_by_risk' => [
                'critical' => $aiThreats->where('risk_level', 'critical')->count(),
                'high' => $aiThreats->where('risk_level', 'high')->count(),
                'medium' => $aiThreats->where('risk_level', 'medium')->count(),
                'low' => $aiThreats->where('risk_level', 'low')->count(),
            ],
            'bdu_reference' => 'https://bdu.fstec.ru/ section "Угрозы безопасности информации систем искусственного интеллекта"',
            'fstec_order_21_compliance' => 'Все угрозы сопоставлены с мерами защиты по Приказу ФСТЭК №21',
            'quantum_threats_monitored' => $quantumThreats->count(),
            'quantum_risk_level' => $this->getQuantumRiskLevel(),
            'quantum_risk_category' => $this->getQuantumRiskCategory(),
            'pqc_migration_status' => 'planned', // Will be updated as migration progresses
        ];
    }

    /**
     * Get quantum response actions based on risk level
     * 
     * @return array Response actions
     */
    public function getQuantumResponseActions(): array
    {
        $level = $this->getQuantumRiskLevel();
        $actions = [];

        if ($level >= self::QUANTUM_RISK_CRITICAL) {
            $actions[] = [
                'action' => 'force_hybrid_encryption',
                'description' => 'Force hybrid encryption (AES-256 + PQC) for all new data',
                'priority' => 'immediate',
            ];
            $actions[] = [
                'action' => 'reencrypt_critical_data',
                'description' => 'Background re-encryption of critical data with hybrid crypto',
                'priority' => 'immediate',
            ];
            $actions[] = [
                'action' => 'disable_classical_only',
                'description' => 'Disable classical-only encryption for new data',
                'priority' => 'immediate',
            ];
            $actions[] = [
                'action' => 'require_mfa_all',
                'description' => 'Require MFA for all operations',
                'priority' => 'immediate',
            ];
            $actions[] = [
                'action' => 'executive_notification',
                'description' => 'Notify executive team of critical quantum risk',
                'priority' => 'immediate',
            ];
        } elseif ($level >= self::QUANTUM_RISK_HIGH) {
            $actions[] = [
                'action' => 'trigger_cooldown',
                'description' => 'Trigger Cooldown for suspicious authentication attempts',
                'priority' => 'high',
            ];
            $actions[] = [
                'action' => 'require_fresh_passkey',
                'description' => 'Require fresh Passkey + liveness for sensitive operations',
                'priority' => 'high',
            ];
            $actions[] = [
                'action' => 'increase_behavioral_sampling',
                'description' => 'Increase behavioral biometrics sampling rate',
                'priority' => 'high',
            ];
            $actions[] = [
                'action' => 'security_team_alert',
                'description' => 'Alert security team for review',
                'priority' => 'high',
            ];
        }

        return $actions;
    }

    /**
     * Get quantum risk level based on threat intelligence and external factors
     * 
     * @return int Risk level (0-3)
     */
    public function getQuantumRiskLevel(): int
    {
        // Check cache first
        $cached = $this->cache->get('quantum_risk_level');
        if ($cached !== null) {
            return (int) $cached;
        }

        // Calculate risk based on multiple factors
        $riskScore = 0;

        // Factor 1: Year-based progression (HNDL becomes more likely over time)
        $currentYear = (int) now()->format('Y');
        if ($currentYear >= 2029) {
            $riskScore += 2; // CRITICAL by default after 2029
        } elseif ($currentYear >= 2027) {
            $riskScore += 1; // HIGH risk from 2027-2028
        }

        // Factor 2: Quantum threat indicators from BDU
        $quantumThreats = $this->getQuantumThreats();
        if ($quantumThreats->where('risk_level', 'critical')->isNotEmpty()) {
            $riskScore += 1;
        }

        // Factor 3: External intelligence (simulated - in production, integrate with threat feeds)
        $externalIndicators = $this->getExternalQuantumIndicators();
        $riskScore += $externalIndicators;

        // Cap at CRITICAL
        $riskLevel = min(self::QUANTUM_RISK_CRITICAL, $riskScore);

        // Cache for 1 hour
        $this->cache->put('quantum_risk_level', $riskLevel, self::CACHE_TTL);

        return $riskLevel;
    }

    /**
     * Get quantum risk category as string
     * 
     * @return string Risk category
     */
    public function getQuantumRiskCategory(): string
    {
        $level = $this->getQuantumRiskLevel();

        return match ($level) {
            self::QUANTUM_RISK_CRITICAL => 'critical',
            self::QUANTUM_RISK_HIGH => 'high',
            self::QUANTUM_RISK_MEDIUM => 'medium',
            self::QUANTUM_RISK_LOW => 'low',
            default => 'unknown',
        };
    }

    /**
     * Get quantum threats from threat model
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getQuantumThreats(): \Illuminate\Database\Eloquent\Collection
    {
        return FstecThreat::where('threat_type', 'КВАНТ')
            ->orWhere('name', 'like', '%квант%')
            ->orWhere('name', 'like', '%Shor%')
            ->orWhere('name', 'like', '%Grover%')
            ->orWhere('description', 'like', '%квант%')
            ->get();
    }

    /**
     * Get external quantum threat indicators
     * 
     * @return int Indicator score (0-2)
     */
    private function getExternalQuantumIndicators(): int
    {
        // In production, integrate with:
        // - NIST PQC timeline updates
        // - NSA/CISA quantum readiness announcements
        // - Academic research breakthroughs
        // - Industry threat intelligence feeds

        // For now, return 0 (no external indicators)
        return 0;
    }

    /**
     * Get current Grover's algorithm risk level
     * 
     * Returns risk level based on:
     * - Current encryption algorithm usage (AES-128 vs AES-256)
     * - Legacy data requiring re-encryption
     * - BDU threat updates
     * 
     * @return array{level: 'low'|'medium'|'high'|'critical', score: float, factors: array}
     */
    public function getGroverRiskLevel(): array
    {
        $cacheKey = 'threat_model:grover_risk_level';
        
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $score = 0.0;
        $factors = [];

        // Factor 1: AES-128 usage (high risk)
        if ($this->hasAES128Data()) {
            $score += 0.4;
            $factors[] = 'Legacy AES-128 data detected';
        }

        // Factor 2: Legacy encryption cast usage
        if ($this->usesLegacyEncryptionCast()) {
            $score += 0.2;
            $factors[] = 'Legacy EncryptedCast in use';
        }

        // Factor 3: BDU threat update for Grover
        if ($this->hasNewGroverThreatFromBDU()) {
            $score += 0.3;
            $factors[] = 'New Grover threat from BDU FSTEC';
        }

        // Factor 4: Key rotation age
        if ($this->isKeyRotationOverdue()) {
            $score += 0.1;
            $factors[] = 'Key rotation overdue';
        }

        $result = [
            'level' => $this->calculateGroverRiskLevel($score),
            'score' => round($score, 2),
            'factors' => $factors,
            'recommendations' => $this->getGroverMitigationRecommendations($score),
        ];

        $this->cache->put($cacheKey, $result, self::CACHE_TTL);

        return $result;
    }

    /**
     * Check if AES-256 is required for given data type based on Grover risk
     * 
     * @param string $dataType (email, phone, passport, inn, biometric_vector, behavioral_profile)
     * @return bool
     */
    public function requiresAES256(string $dataType): bool
    {
        $groverRisk = $this->getGroverRiskLevel();
        
        // Critical data types always require AES-256
        $criticalTypes = ['biometric_vector', 'behavioral_profile', 'passport', 'inn'];
        
        if (in_array($dataType, $criticalTypes)) {
            return true;
        }
        
        // If Grover risk is high/medium, require AES-256 for all PII
        return in_array($groverRisk['level'], ['high', 'critical']);
    }

    /**
     * Get recommended cooldown multiplier based on Grover risk
     * 
     * Higher Grover risk = longer cooldowns for PII operations
     * 
     * @return float (1.0 = normal, 2.0 = double cooldown)
     */
    public function getGroverCooldownMultiplier(): float
    {
        $groverRisk = $this->getGroverRiskLevel();
        
        return match ($groverRisk['level']) {
            'low' => 1.0,
            'medium' => 1.5,
            'high' => 2.0,
            'critical' => 3.0,
            default => 1.0,
        };
    }

    /**
     * Calculate Grover risk level from score
     * 
     * @param float $score Risk score (0-1)
     * @return string Risk level
     */
    private function calculateGroverRiskLevel(float $score): string
    {
        return match (true) {
            $score >= 0.9 => 'critical',
            $score >= 0.7 => 'high',
            $score >= 0.4 => 'medium',
            default => 'low',
        };
    }

    /**
     * Get Grover mitigation recommendations
     * 
     * @param float $score Risk score
     * @return array Recommendations
     */
    private function getGroverMitigationRecommendations(float $score): array
    {
        $recommendations = [];

        if ($score > 0.4) {
            $recommendations[] = 'Migrate all AES-128 data to AES-256-GCM';
        }

        if ($score > 0.6) {
            $recommendations[] = 'Enable AES256EncryptedCast for all PII fields';
            $recommendations[] = 'Increase key rotation frequency to monthly';
        }

        if ($score > 0.8) {
            $recommendations[] = 'Implement hybrid crypto (AES-256 + ML-KEM) for critical data';
            $recommendations[] = 'Enable quantum-resistant key exchange';
        }

        return $recommendations;
    }

    /**
     * Check if system has AES-128 encrypted data
     * 
     * @return bool
     */
    private function hasAES128Data(): bool
    {
        // Check database for legacy encrypted data
        return config('security.has_legacy_aes128_data', false);
    }

    /**
     * Check if system uses legacy encryption cast
     * 
     * @return bool
     */
    private function usesLegacyEncryptionCast(): bool
    {
        return config('security.uses_legacy_encryption_cast', false);
    }

    /**
     * Check if BDU has new Grover-related threats
     * 
     * @return bool
     */
    private function hasNewGroverThreatFromBDU(): bool
    {
        $groverThreats = $this->getQuantumThreats()
            ->filter(fn ($t) => str_contains($t->name, 'Grover') || str_contains($t->description, 'Grover'));
        
        return $groverThreats->isNotEmpty();
    }

    /**
     * Check if key rotation is overdue
     * 
     * @return bool
     */
    private function isKeyRotationOverdue(): bool
    {
        $lastRotation = config('security.last_key_rotation_date');
        if (!$lastRotation) {
            return true;
        }
        
        $daysSinceRotation = now()->diffInDays($lastRotation);
        return $daysSinceRotation > 90; // Quarterly rotation
    }
}
