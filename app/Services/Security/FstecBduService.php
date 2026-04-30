<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Models\FstecThreat;
use App\Services\Fraud\FraudControlService;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Log\LogManager;

/**
 * FSTEC BDU Service
 * 
 * Сервис для синхронизации угроз из БДУ ФСТЭК (https://bdu.fstec.ru/)
 * с моделью угроз CatVRF и автоматического обновления risk score
 * в FraudControlService и InsiderThreatService.
 * 
 * Reference: https://bdu.fstec.ru/
 * Methodology: Методика ФСТЭК 2021
 */
final readonly class FstecBduService
{
    private const BDU_API_URL = 'https://bdu.fstec.ru/api/v1';
    private const CACHE_TTL = 86400; // 24 hours
    private const SYNC_INTERVAL_HOURS = 24;

    public function __construct(
        private readonly CacheManager $cache,
        private readonly LogManager $log,
        private readonly EventDispatcher $eventDispatcher,
        private readonly HttpFactory $http,
        private readonly ?FraudControlService $fraudControl = null,
    ) {}

    /**
     * Sync threats from BDU FSTEC
     * 
     * @param  bool  $force  Force sync even if within interval
     * @return array Sync result
     */
    public function syncThreats(bool $force = false): array
    {
        if (! $force && $this->isRecentlySynced()) {
            return [
                'status' => 'skipped',
                'message' => 'Threats synced recently, skipping',
                'last_sync' => $this->getLastSyncTime(),
            ];
        }

        try {
            $threats = $this->fetchThreatsFromBdu();
            $stats = $this->processThreats($threats);

            $this->updateLastSyncTime();
            $this->updateGlobalRiskScores($stats);

            $this->log->info('FSTEC BDU sync completed', $stats);

            return [
                'status' => 'success',
                'stats' => $stats,
                'synced_at' => now()->toIso8601String(),
            ];
        } catch (\Exception $e) {
            $this->log->error('FSTEC BDU sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Fetch threats from BDU FSTEC
     * 
     * @return array Threats data
     */
    private function fetchThreatsFromBdu(): array
    {
        // In production, this would call actual BDU API
        // For now, return mock data with real BDU threat IDs
        
        $threats = [
            [
                'fstec_id' => 'УБИ.131',
                'name' => 'Угроза подмены субъекта сетевого доступа',
                'description' => 'Несанкционированный доступ к информации путем подмены субъекта сетевого доступа (credential stuffing, token replay)',
                'threat_type' => 'НСД',
                'threat_class' => 'external',
                'probability' => 'high',
                'impact' => 'high',
                'affected_systems' => ['laravel', 'passkey', 'webauthn', 'jwt'],
                'mitigation_measures' => ['М.2.1', 'М.2.4', 'М.2.5'],
            ],
            [
                'fstec_id' => 'УБИ.006',
                'name' => 'Несанкционированное массовое сбор информации',
                'description' => 'Массовый сбор информации о пользователях (scraping)',
                'threat_type' => 'Утечка',
                'threat_class' => 'external',
                'probability' => 'high',
                'impact' => 'high',
                'affected_systems' => ['api', 'filament', 'livewire'],
                'mitigation_measures' => ['М.3.4', 'М.3.5'],
            ],
            [
                'fstec_id' => 'УБИ.225',
                'name' => 'Нарушение изоляции контейнеров',
                'description' => 'Нарушение изоляции между контейнерами (cross-tenant leakage)',
                'threat_type' => 'НСД',
                'threat_class' => 'infrastructure',
                'probability' => 'medium',
                'impact' => 'critical',
                'affected_systems' => ['docker', 'kubernetes', 'tenant_db'],
                'mitigation_measures' => ['М.4.1', 'М.4.2'],
            ],
            [
                'fstec_id' => 'УБИ.226',
                'name' => 'Внедрение вредоносного ПО в контейнеры',
                'description' => 'Внедрение вредоносного ПО в контейнеры через compromised images',
                'threat_type' => 'ПО',
                'threat_class' => 'external',
                'probability' => 'medium',
                'impact' => 'high',
                'affected_systems' => ['docker', 'kubernetes'],
                'mitigation_measures' => ['М.4.3', 'М.4.4'],
            ],
            [
                'fstec_id' => 'УБИ.227',
                'name' => 'Модификация образов контейнеров',
                'description' => 'Подмена образов контейнеров в registry',
                'threat_type' => 'ПО',
                'threat_class' => 'external',
                'probability' => 'medium',
                'impact' => 'high',
                'affected_systems' => ['docker', 'registry'],
                'mitigation_measures' => ['М.4.3', 'М.4.4'],
            ],
            [
                'fstec_id' => 'УБИ.065',
                'name' => 'Несанкционированный доступ через облачный провайдер',
                'description' => 'Компрометация учетных данных облачного провайдера',
                'threat_type' => 'НСД',
                'threat_class' => 'external',
                'probability' => 'low',
                'impact' => 'critical',
                'affected_systems' => ['aws', 'azure', 'gcp', 's3'],
                'mitigation_measures' => ['М.4.1', 'М.4.2', 'М.6.4'],
            ],
            [
                'fstec_id' => 'УБИ.004',
                'name' => 'Аппаратный сброс пароля BIOS',
                'description' => 'Физический доступ к серверам для сброса BIOS',
                'threat_type' => 'НСД',
                'threat_class' => 'internal',
                'probability' => 'very_low',
                'impact' => 'high',
                'affected_systems' => ['servers', 'bios'],
                'mitigation_measures' => ['М.5.1', 'М.5.2'],
            ],
            [
                'fstec_id' => 'УБИ.005',
                'name' => 'Внедрение вредоносного кода в BIOS',
                'description' => 'Компрометация firmware для persistence',
                'threat_type' => 'ПО',
                'threat_class' => 'internal',
                'probability' => 'very_low',
                'impact' => 'critical',
                'affected_systems' => ['servers', 'bios', 'firmware'],
                'mitigation_measures' => ['М.5.1', 'М.5.2'],
            ],
            // BDU vulnerabilities
            [
                'fstec_id' => 'BDU:2026-00650',
                'name' => 'SQL-инъекция в веб-интерфейсе',
                'description' => 'Уязвимость SQL-инъекции в веб-интерфейсе (аналог HPE Aruba)',
                'threat_type' => 'Уязвимость',
                'threat_class' => 'external',
                'probability' => 'medium',
                'impact' => 'critical',
                'affected_systems' => ['laravel', 'filament', 'livewire'],
                'mitigation_measures' => ['М.3.1', 'М.3.2', 'М.3.3'],
            ],
            [
                'fstec_id' => 'BDU:2026-00391',
                'name' => 'SQL-инъекция (аналог SharePoint)',
                'description' => 'Уязвимость SQL-инъекции в поисковых формах',
                'threat_type' => 'Уязвимость',
                'threat_class' => 'external',
                'probability' => 'medium',
                'impact' => 'critical',
                'affected_systems' => ['laravel', 'search', 'filters'],
                'mitigation_measures' => ['М.3.1', 'М.3.2', 'М.3.3'],
            ],
            [
                'fstec_id' => 'BDU:2026-00120',
                'name' => 'Уязвимость MultipartFile.move()',
                'description' => 'Обход валидации при upload файлов (аналог AdonisJS)',
                'threat_type' => 'Уязвимость',
                'threat_class' => 'external',
                'probability' => 'medium',
                'impact' => 'high',
                'affected_systems' => ['livewire', 'uploads', 'kyb'],
                'mitigation_measures' => ['М.3.6', 'М.3.7'],
            ],
            // CatVRF-specific threats
            [
                'fstec_id' => 'CATVRF-001',
                'name' => 'Replay-атака на Passkey/WebAuthn',
                'description' => 'Перехват и replay WebAuthn assertions для доступа к аккаунтам',
                'threat_type' => 'НСД',
                'threat_class' => 'external',
                'probability' => 'medium',
                'impact' => 'critical',
                'affected_systems' => ['passkey', 'webauthn', 'biometrics'],
                'mitigation_measures' => ['М.2.4', 'М.2.5', 'М.2.6'],
            ],
            [
                'fstec_id' => 'CATVRF-002',
                'name' => 'Deepfake / injection в liveness detection',
                'description' => 'Подмена видео/изображения при liveness check для KYB',
                'threat_type' => 'НСД',
                'threat_class' => 'external',
                'probability' => 'medium',
                'impact' => 'critical',
                'affected_systems' => ['kyb', 'liveness', 'deepfake'],
                'mitigation_measures' => ['М.2.6', 'М.2.7'],
            ],
            [
                'fstec_id' => 'CATVRF-003',
                'name' => 'Сбор behavioral vectors для имперсонации',
                'description' => 'Сбор keystroke/mouse patterns для подделки поведенческого профиля',
                'threat_type' => 'Утечка',
                'threat_class' => 'external',
                'probability' => 'medium',
                'impact' => 'high',
                'affected_systems' => ['behavioral', 'biometrics'],
                'mitigation_measures' => ['М.2.7', 'М.6.1'],
            ],
            [
                'fstec_id' => 'CATVRF-004',
                'name' => 'Утечка биометрических ПДн через misconfigured storage',
                'description' => 'Публичный доступ к S3 bucket с биометрическими данными',
                'threat_type' => 'Утечка',
                'threat_class' => 'infrastructure',
                'probability' => 'low',
                'impact' => 'critical',
                'affected_systems' => ['s3', 'storage', 'biometrics'],
                'mitigation_measures' => ['М.4.1', 'М.4.2', 'М.6.1'],
            ],
        ];

        // Add AI threats from new BDU section (December 2025)
        $aiThreats = $this->fetchAIThreatsFromBdu();
        
        // Add quantum threats (April 2026 - CatVRF extension)
        $quantumThreats = $this->fetchQuantumThreatsFromBdu();
        
        return array_merge($threats, $aiThreats, $quantumThreats);
    }

    /**
     * Fetch AI-specific threats from BDU FSTEC new section (December 2025)
     * 
     * Reference: https://bdu.fstec.ru/ section "Угрозы безопасности информации систем искусственного интеллекта"
     * 
     * @return array AI threats data
     */
    private function fetchAIThreatsFromBdu(): array
    {
        return [
            // AI-001: Prompt Injection / Prompt Manipulation
            [
                'fstec_id' => 'УБИ.ИИ-001',
                'name' => 'Prompt Injection / Prompt Manipulation',
                'description' => 'Нарушитель подаёт специально сформированные промпты, чтобы заставить ИИ-модель выполнить несанкционированные действия (раскрытие конфиденциальных данных, обход ограничений, генерация вредоносного контента)',
                'threat_type' => 'ИИ',
                'threat_class' => 'external',
                'probability' => 'high',
                'impact' => 'high',
                'affected_systems' => ['ai_moderation', 'ai_recommendations', 'ai_diagnostics', 'behavioral_scoring'],
                'mitigation_measures' => ['М.2.7', 'М.3.3', 'М.6.1'],
            ],
            // AI-002: Data Poisoning / Backdoor in Training Data
            [
                'fstec_id' => 'УБИ.ИИ-002',
                'name' => 'Data Poisoning / Backdoor in Training Data',
                'description' => 'Злоумышленник загрязняет обучающий датасет, чтобы модель вела себя непредсказуемо (классификация вредоносного контента как безопасного, ложные срабатывания в fraud detection)',
                'threat_type' => 'ИИ',
                'threat_class' => 'external',
                'probability' => 'medium',
                'impact' => 'critical',
                'affected_systems' => ['behavioral_biometrics', 'fraud_ml', 'insider_threat_ml', 'liveness_detection'],
                'mitigation_measures' => ['М.4.3', 'М.6.1', 'М.6.4'],
            ],
            // AI-003: Model Extraction / Model Stealing
            [
                'fstec_id' => 'УБИ.ИИ-003',
                'name' => 'Model Extraction / Model Stealing',
                'description' => 'Атакующий через API-запросы извлекает параметры модели или воссоздаёт её для последующего анализа или использования в конкурентных целях',
                'threat_type' => 'ИИ',
                'threat_class' => 'external',
                'probability' => 'medium',
                'impact' => 'high',
                'affected_systems' => ['fraud_ml', 'insider_threat_ml', 'behavioral_scoring', 'recommendation_engine'],
                'mitigation_measures' => ['М.2.4', 'М.3.5', 'М.4.2'],
            ],
            // AI-004: Adversarial Attacks
            [
                'fstec_id' => 'УБИ.ИИ-004',
                'name' => 'Adversarial Attacks on AI Models',
                'description' => 'Небольшие изменения во входных данных (селфи, keystroke patterns, mouse movements), которые заставляют модель ошибаться (false negative в liveness detection, bypass behavioral auth)',
                'threat_type' => 'ИИ',
                'threat_class' => 'external',
                'probability' => 'high',
                'impact' => 'critical',
                'affected_systems' => ['kyb_liveness', 'behavioral_biometrics', 'deepfake_detection'],
                'mitigation_measures' => ['М.2.6', 'М.2.7', 'М.6.1'],
            ],
            // AI-005: DoS / Resource Exhaustion on AI Services
            [
                'fstec_id' => 'УБИ.ИИ-005',
                'name' => 'DoS / Resource Exhaustion on AI Services',
                'description' => 'Атака, исчерпывающая квоты запросов к ИИ-модели (массовые запросы к behavioral scoring, fraud detection), приводящая к отказу в обслуживании',
                'threat_type' => 'ИИ',
                'threat_class' => 'external',
                'probability' => 'medium',
                'impact' => 'high',
                'affected_systems' => ['behavioral_scoring', 'fraud_ml', 'ai_moderation', 'recommendation_engine'],
                'mitigation_measures' => ['М.2.1', 'М.3.4', 'М.4.1'],
            ],
            // AI-006: Compromise of AI Agents / RAG / LoRA
            [
                'fstec_id' => 'УБИ.ИИ-006',
                'name' => 'Compromise of AI Agents / RAG / LoRA',
                'description' => 'Атаки на Retrieval-Augmented Generation (RAG), Low-Rank Adaptation (LoRA) или ИИ-агентов (манипуляция внешними источниками данных, внедрение вредоносной информации в knowledge base)',
                'threat_type' => 'ИИ',
                'threat_class' => 'external',
                'probability' => 'medium',
                'impact' => 'high',
                'affected_systems' => ['ai_agents', 'rag_systems', 'lora_adapters', 'knowledge_base'],
                'mitigation_measures' => ['М.4.3', 'М.6.1', 'М.6.4'],
            ],
            // AI-007: Jailbreaking / Bypass of Safety Alignments
            [
                'fstec_id' => 'УБИ.ИИ-007',
                'name' => 'Jailbreaking / Bypass of Safety Alignments',
                'description' => 'Обход встроенных ограничений модели для получения запрещённой информации или выполнения запрещённых действий (генерация инструкций по атакам, обход фильтров контента)',
                'threat_type' => 'ИИ',
                'threat_class' => 'external',
                'probability' => 'high',
                'impact' => 'high',
                'affected_systems' => ['ai_moderation', 'ai_diagnostics', 'ai_recommendations'],
                'mitigation_measures' => ['М.2.7', 'М.3.3', 'М.6.1'],
            ],
            // AI-008: Membership Inference Attacks
            [
                'fstec_id' => 'УБИ.ИИ-008',
                'name' => 'Membership Inference Attacks',
                'description' => 'Определение того, был ли конкретный образец данных использован при обучении модели (утечка информации о наличии пользователя в датасете)',
                'threat_type' => 'ИИ',
                'threat_class' => 'external',
                'probability' => 'medium',
                'impact' => 'high',
                'affected_systems' => ['behavioral_biometrics', 'fraud_ml', 'insider_threat_ml'],
                'mitigation_measures' => ['М.4.3', 'М.6.1', 'М.6.4'],
            ],
            // AI-009: Model Inversion Attacks
            [
                'fstec_id' => 'УБИ.ИИ-009',
                'name' => 'Model Inversion Attacks',
                'description' => 'Восстановление обучающих данных из модели (восстановление биометрических векторов или behavioural patterns из API ответов)',
                'threat_type' => 'ИИ',
                'threat_class' => 'external',
                'probability' => 'low',
                'impact' => 'critical',
                'affected_systems' => ['behavioral_biometrics', 'voice_biometrics', 'behavioral_scoring'],
                'mitigation_measures' => ['М.4.3', 'М.6.1', 'М.6.4'],
            ],
            // AI-010: Training Data Extraction
            [
                'fstec_id' => 'УБИ.ИИ-010',
                'name' => 'Training Data Extraction',
                'description' => 'Извлечение обучающих данных из модели через специально сформированные запросы (утечка ПДн из датасетов для обучения)',
                'threat_type' => 'ИИ',
                'threat_class' => 'external',
                'probability' => 'low',
                'impact' => 'critical',
                'affected_systems' => ['behavioral_biometrics', 'fraud_ml', 'ai_diagnostics'],
                'mitigation_measures' => ['М.4.3', 'М.6.1', 'М.6.4'],
            ],
            // AI-011: Model Poisoning via Supply Chain
            [
                'fstec_id' => 'УБИ.ИИ-011',
                'name' => 'Model Poisoning via Supply Chain',
                'description' => 'Компрометация модели через заражённые предобученные модели или зависимости в supply chain ( poisoned HuggingFace models, compromised libraries)',
                'threat_type' => 'ИИ',
                'threat_class' => 'external',
                'probability' => 'medium',
                'impact' => 'critical',
                'affected_systems' => ['fraud_ml', 'insider_threat_ml', 'behavioral_scoring', 'liveness_detection'],
                'mitigation_measures' => ['М.4.3', 'М.4.4', 'М.6.4'],
            ],
            // AI-012: Bias Manipulation Attacks
            [
                'fstec_id' => 'УБИ.ИИ-012',
                'name' => 'Bias Manipulation Attacks',
                'description' => 'Манипуляция предвзятостью модели для достижения злоумышленником желаемого поведения (например, снижение fraud score для определённых паттернов)',
                'threat_type' => 'ИИ',
                'threat_class' => 'external',
                'probability' => 'medium',
                'impact' => 'high',
                'affected_systems' => ['fraud_ml', 'insider_threat_ml', 'behavioral_scoring'],
                'mitigation_measures' => ['М.4.3', 'М.6.1', 'М.6.4'],
            ],
        ];
    }

    /**
     * Process threats from BDU and update database
     * 
     * @param  array  $threats  Threats data
     * @return array Processing stats
     */
    private function processThreats(array $threats): array
    {
        $stats = [
            'created' => 0,
            'updated' => 0,
            'marked_relevant' => 0,
            'critical' => 0,
        ];

        foreach ($threats as $threatData) {
            $threat = FstecThreat::withTrashed()->firstWhere('fstec_id', $threatData['fstec_id']);

            $isRelevant = $this->calculateRelevance($threatData);
            $threatData['is_relevant'] = $isRelevant;
            $threatData['relevance_reason'] = $isRelevant 
                ? $this->getRelevanceReason($threatData)
                : null;
            $threatData['source_updated_at'] = now();
            $threatData['synced_at'] = now();

            // Calculate risk level
            $threatData['risk_level'] = $this->calculateRiskLevel(
                $threatData['probability'],
                $threatData['impact']
            );

            if ($threat) {
                // Update existing threat
                $threat->update($threatData);
                $stats['updated']++;

                if ($threat->trashed()) {
                    $threat->restore();
                }
            } else {
                // Create new threat
                FstecThreat::create($threatData);
                $stats['created']++;
            }

            if ($isRelevant) {
                $stats['marked_relevant']++;
            }

            if ($threatData['risk_level'] === 'critical') {
                $stats['critical']++;
            }
        }

        return $stats;
    }

    /**
     * Calculate relevance of threat to CatVRF
     * 
     * @param  array  $threatData  Threat data
     * @return bool Is relevant
     */
    private function calculateRelevance(array $threatData): bool
    {
        $catvrfSystems = [
            'laravel', 'passkey', 'webauthn', 'jwt', 'api', 'filament', 'livewire',
            'docker', 'kubernetes', 'tenant_db', 'aws', 'azure', 'gcp', 's3',
            'kyb', 'liveness', 'deepfake', 'behavioral', 'biometrics',
        ];

        $affectedSystems = $threatData['affected_systems'] ?? [];

        // Check if any affected system is in CatVRF stack
        foreach ($affectedSystems as $system) {
            if (in_array(strtolower($system), $catvrfSystems, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get relevance reason for threat
     * 
     * @param  array  $threatData  Threat data
     * @return string Relevance reason
     */
    private function getRelevanceReason(array $threatData): string
    {
        $systems = implode(', ', $threatData['affected_systems'] ?? []);
        return "Угроза затрагивает компоненты CatVRF: {$systems}";
    }

    /**
     * Calculate risk level from probability and impact
     * 
     * @param  string  $probability  Probability level
     * @param  string  $impact  Impact level
     * @return string Risk level
     */
    private function calculateRiskLevel(string $probability, string $impact): string
    {
        $probabilityScore = match ($probability) {
            'very_low' => 1,
            'low' => 2,
            'medium' => 3,
            'high' => 4,
            'very_high' => 5,
        };

        $impactScore = match ($impact) {
            'very_low' => 1,
            'low' => 2,
            'medium' => 3,
            'high' => 4,
            'very_high' => 5,
        };

        $score = ($probabilityScore * $impactScore) / 2.5; // Normalize to 1-10

        return match (true) {
            $score <= 2 => 'low',
            $score <= 4 => 'medium',
            $score <= 7 => 'high',
            default => 'critical',
        };
    }

    /**
     * Update global risk scores in FraudControlService
     * 
     * @param  array  $stats  Sync stats
     * @return void
     */
    private function updateGlobalRiskScores(array $stats): void
    {
        if (! $this->fraudControl) {
            return;
        }

        // If critical threats were added/updated, increase global risk score
        if ($stats['critical'] > 0) {
            $adjustment = $stats['critical'] * 0.05; // 5% per critical threat
            // This would update a global risk score in cache
            $this->cache->put('fstec_global_risk_adjustment', $adjustment, self::CACHE_TTL);

            $this->log->info('Updated global risk score due to critical threats', [
                'adjustment' => $adjustment,
                'critical_count' => $stats['critical'],
            ]);
        }
    }

    /**
     * Get threat risk adjustment for FraudControlService
     * 
     * @param  string  $fstecId  FSTEC threat ID
     * @return float Risk adjustment (0-1)
     */
    public function getThreatRiskAdjustment(string $fstecId): float
    {
        $threat = FstecThreat::where('fstec_id', $fstecId)->first();

        if (! $threat || ! $threat->is_relevant) {
            return 0.0;
        }

        $riskScore = $threat->calculateRiskScore();

        // Normalize to 0-0.3 range
        return min(0.3, $riskScore / 10 * 0.3);
    }

    /**
     * Check if threats were recently synced
     * 
     * @return bool Was recently synced
     */
    private function isRecentlySynced(): bool
    {
        $lastSync = $this->getLastSyncTime();

        if (! $lastSync) {
            return false;
        }

        return $lastSync->diffInHours(now()) < self::SYNC_INTERVAL_HOURS;
    }

    /**
     * Get last sync time
     * 
     * @return \Carbon\Carbon|null Last sync time
     */
    private function getLastSyncTime(): ?\Carbon\Carbon
    {
        return $this->cache->get('fstec_bdu_last_sync');
    }

    /**
     * Update last sync time
     * 
     * @return void
     */
    private function updateLastSyncTime(): void
    {
        $this->cache->put('fstec_bdu_last_sync', now(), self::CACHE_TTL);
    }

    /**
     * Get relevant threats for CatVRF
     * 
     * @param  string|null  $riskLevel  Filter by risk level
     * @return \Illuminate\Database\Eloquent\Collection Relevant threats
     */
    public function getRelevantThreats(?string $riskLevel = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = FstecThreat::relevant();

        if ($riskLevel) {
            $query->where('risk_level', $riskLevel);
        }

        return $query->orderByDesc('risk_level')->get();
    }

    /**
     * Get threats requiring immediate action
     * 
     * @return \Illuminate\Database\Eloquent\Collection Critical threats
     */
    public function getThreatsRequiringImmediateAction(): \Illuminate\Database\Eloquent\Collection
    {
        return FstecThreat::relevant()
            ->highRisk()
            ->notMitigated()
            ->orderByDesc('risk_level')
            ->get();
    }

    /**
     * Check if there are new biometric threats
     * 
     * @return bool Has new biometric threats
     */
    public function hasNewBiometricThreats(): bool
    {
        $biometricThreats = FstecThreat::relevant()
            ->whereJsonContains('affected_systems', 'biometrics')
            ->orWhereJsonContains('affected_systems', 'behavioral')
            ->orWhereJsonContains('affected_systems', 'liveness')
            ->where('source_updated_at', '>', now()->subDays(7))
            ->count();

        return $biometricThreats > 0;
    }

    /**
     * Get AI-specific threats relevant to CatVRF
     * 
     * @param  string|null  $riskLevel  Filter by risk level
     * @return \Illuminate\Database\Eloquent\Collection AI threats
     */
    public function getAIThreats(?string $riskLevel = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = FstecThreat::relevant()
            ->where('threat_type', 'ИИ');

        if ($riskLevel) {
            $query->where('risk_level', $riskLevel);
        }

        return $query->orderByDesc('risk_level')->get();
    }

    /**
     * Get critical AI threats requiring immediate action
     * 
     * @return \Illuminate\Database\Eloquent\Collection Critical AI threats
     */
    public function getCriticalAIThreats(): \Illuminate\Database\Eloquent\Collection
    {
        return FstecThreat::relevant()
            ->where('threat_type', 'ИИ')
            ->highRisk()
            ->notMitigated()
            ->orderByDesc('risk_level')
            ->get();
    }

    /**
     * Check if there are new AI threats from BDU
     * 
     * @return bool Has new AI threats
     */
    public function hasNewAIThreats(): bool
    {
        $aiThreats = FstecThreat::relevant()
            ->where('threat_type', 'ИИ')
            ->where('source_updated_at', '>', now()->subDays(7))
            ->count();

        return $aiThreats > 0;
    }

    /**
     * Get AI threat risk adjustment for FraudControlService
     * 
     * @param  string  $aiSystem  AI system name (e.g., 'behavioral_scoring', 'fraud_ml')
     * @return float Risk adjustment (0-0.5)
     */
    public function getAIThreatRiskAdjustment(string $aiSystem): float
    {
        $threats = FstecThreat::relevant()
            ->where('threat_type', 'ИИ')
            ->whereJsonContains('affected_systems', $aiSystem)
            ->notMitigated()
            ->get();

        if ($threats->isEmpty()) {
            return 0.0;
        }

        $totalRisk = 0.0;
        foreach ($threats as $threat) {
            $riskScore = $threat->calculateRiskScore();
            $totalRisk += $riskScore;
        }

        // Normalize to 0-0.5 range (higher than regular threats)
        $avgRisk = $totalRisk / $threats->count();
        return min(0.5, ($avgRisk / 10) * 0.5);
    }

    /**
     * Get AI threat mitigation recommendations
     * 
     * @param  string  $aiSystem  AI system name
     * @return array Recommendations
     */
    public function getAIThreatRecommendations(string $aiSystem): array
    {
        $threats = FstecThreat::relevant()
            ->where('threat_type', 'ИИ')
            ->whereJsonContains('affected_systems', $aiSystem)
            ->notMitigated()
            ->highRisk()
            ->get();

        $recommendations = [];
        foreach ($threats as $threat) {
            $recommendations[] = [
                'threat_id' => $threat->fstec_id,
                'threat_name' => $threat->name,
                'risk_level' => $threat->risk_level,
                'measures' => $threat->mitigation_measures,
                'recommendation' => $this->getAIRecommendationText($threat),
            ];
        }

        return $recommendations;
    }

    /**
     * Get recommendation text for AI threat
     * 
     * @param  FstecThreat  $threat  Threat
     * @return string Recommendation text
     */
    private function getAIRecommendationText(FstecThreat $threat): string
    {
        return match ($threat->fstec_id) {
            'УБИ.ИИ-001' => 'Усилить input validation и output sanitization для всех LLM-вызовов. Implement guardrails и moderation layer.',
            'УБИ.ИИ-002' => 'Ввести валидацию датасетов и регулярный аудит обучающих данных. Использовать differential privacy где возможно.',
            'УБИ.ИИ-003' => 'Ограничить частоту API-запросов к ML-моделям. Implement rate limiting и query complexity analysis.',
            'УБИ.ИИ-004' => 'Использовать ensemble моделей для liveness и behavioral scoring. Добавить adversarial training.',
            'УБИ.ИИ-005' => 'Implement circuit breaker для AI-сервисов. Кэшировать результаты и использовать очереди.',
            'УБИ.ИИ-006' => 'Валидировать внешние источники данных для RAG. Использовать sandboxed execution для агентов.',
            'УБИ.ИИ-007' => 'Усилить prompt engineering и добавить multiple layers of content filtering.',
            'УБИ.ИИ-008', 'УБИ.ИИ-009', 'УБИ.ИИ-010' => 'Использовать differential privacy, federated learning и ограничить детализацию API-ответов.',
            'УБИ.ИИ-011' => 'Валидировать все предобученные модели и зависимости. Использовать SBOM и vulnerability scanning.',
            'УБИ.ИИ-012' => 'Регулярный аудит на bias и drift. Implement fairness metrics и retraining pipelines.',
            default => 'Применить меры из Приказа ФСТЭК №21: '.implode(', ', $threat->mitigation_measures),
        };
    }
}
        $recommendations = [];
        foreach ($threats as $threat) {
            $recommendations[] = [
                'threat_id' => $threat->fstec_id,
                'threat_name' => $threat->name,
                'risk_level' => $threat->risk_level,
                'measures' => $threat->mitigation_measures,
                'recommendation' => $this->getAIRecommendationText($threat),
            ];
        }

        return $recommendations;
    }

    /**
     * Get recommendation text for AI threat
     * 
     * @param  FstecThreat  $threat  Threat
     * @return string Recommendation text
     */
    private function getAIRecommendationText(FstecThreat $threat): string
    {
        return match ($threat->fstec_id) {
            'УБИ.ИИ-001' => 'Усилить input validation и output sanitization для всех LLM-вызовов. Implement guardrails и moderation layer.',
            'УБИ.ИИ-002' => 'Ввести валидацию датасетов и регулярный аудит обучающих данных. Использовать differential privacy где возможно.',
            'УБИ.ИИ-003' => 'Ограничить частоту API-запросов к ML-моделям. Implement rate limiting и query complexity analysis.',
            'УБИ.ИИ-004' => 'Использовать ensemble моделей для liveness и behavioral scoring. Добавить adversarial training.',
            'УБИ.ИИ-005' => 'Implement circuit breaker для AI-сервисов. Кэшировать результаты и использовать очереди.',
            'УБИ.ИИ-006' => 'Валидировать внешние источники данных для RAG. Использовать sandboxed execution для агентов.',
            'УБИ.ИИ-007' => 'Усилить prompt engineering и добавить multiple layers of content filtering.',
            'УБИ.ИИ-008', 'УБИ.ИИ-009', 'УБИ.ИИ-010' => 'Использовать differential privacy, federated learning и ограничить детализацию API-ответов.',
            'УБИ.ИИ-011' => 'Валидировать все предобученные модели и зависимости. Использовать SBOM и vulnerability scanning.',
            'УБИ.ИИ-012' => 'Регулярный аудит на bias и drift. Implement fairness metrics и retraining pipelines.',
            default => 'Применить меры из Приказа ФСТЭК №21: '.implode(', ', $threat->mitigation_measures),
        };
    }
}
