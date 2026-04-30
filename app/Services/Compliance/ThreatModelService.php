<?php

declare(strict_types=1);

namespace App\Services\Compliance;

use App\Models\FstecThreat;
use App\Models\FstecVulnerability;
use Psr\Log\LoggerInterface;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;

/**
 * Threat Model Service
 * 
 * Manages the threat model for CatVRF ISPDn (Information System of Personal Data).
 * Integrates with FSTEC BDU for dynamic threat updates.
 * Generates documentation for Roskomnadzor audits.
 * 
 * Reference: Методика ФСТЭК 2021
 * Protection Level: УЗ-3 (for biometric data)
 */
final readonly class ThreatModelService
{
    use WithAuditLogging;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly FstecBduService $bduService,
        private readonly AuditService $audit,
    ) {}

    /**
     * Get current threat model for CatVRF
     */
    public function getThreatModel(): array
    {
        return [
            'system_description' => $this->getSystemDescription(),
            'threat_actor_model' => $this->getThreatActorModel(),
            'relevant_threats' => $this->getRelevantThreats(),
            'relevant_vulnerabilities' => $this->getRelevantVulnerabilities(),
            'risk_matrix' => $this->getRiskMatrix(),
            'protection_level' => $this->getProtectionLevel(),
            'mitigation_measures' => $this->getMitigationMeasures(),
            'last_updated' => now()->toIso8601String(),
        ];
    }

    /**
     * Get system description for threat model
     */
    private function getSystemDescription(): array
    {
        return [
            'system_name' => 'CatVRF - AI-powered Healthcare Marketplace',
            'system_type' => 'Multi-tenant SaaS Marketplace',
            'isdpn_name' => 'ИСПДн CatVRF',
            'protection_level' => 'УЗ-3',
            
            'data_categories' => [
                'personal_data' => [
                    'full_name',
                    'email',
                    'phone',
                    'address',
                    'date_of_birth',
                ],
                'biometric_data' => [
                    'face_id',
                    'liveness_data',
                    'behavioral_patterns',
                    'voice_templates',
                ],
                'special_categories' => [
                    'kyb_documents',
                    'director_passport',
                    'inn_ogrn',
                ],
            ],
            
            'data_subjects_count' => config('ispdn.subjects_count', 100000),
            'tenants_count' => config('ispdn.tenants_count', 1000),
            
            'processing_contours' => [
                'central_db' => 'Central database with tenant metadata',
                'tenant_db' => 'Tenant-specific databases',
                'frontend' => 'Vue/Livewire frontend',
                'api' => 'REST API endpoints',
                'filament' => 'Admin panel for staff',
            ],
            
            'data_localization' => 'Russian Federation',
        ];
    }

    /**
     * Get threat actor model (Модель нарушителя)
     */
    private function getThreatActorModel(): array
    {
        return [
            'external_threats' => [
                'type' => 'Внешний нарушитель',
                'motivation' => 'Финансовая выгода, продажа баз, конкуренция',
                'capabilities' => [
                    'SQL injection',
                    'API abuse',
                    'Credential stuffing',
                    'Phishing',
                    'Exploit vulnerabilities',
                    'Bot scraping',
                ],
                'resources' => 'Средние/высокие (ботнеты, zero-day)',
                'possibility_level' => '3 (базовый) - 1 (высокий)',
            ],
            
            'internal_threats' => [
                'type' => 'Внутренний нарушитель (insider)',
                'motivation' => 'Личная выгода, месть, сговор',
                'capabilities' => [
                    'Легитимный доступ через Filament/API',
                    'Bulk export',
                    'Raw queries',
                    'Hunting по контактам',
                ],
                'resources' => 'Низкие-средние (но высокий уровень доступа)',
                'possibility_level' => '2 (средний)',
                'relevance' => 'Очень высокая',
            ],
            
            'accidental_threats' => [
                'type' => 'Случайный нарушитель',
                'motivation' => 'Ошибка администратора, потеря устройства',
                'capabilities' => [
                    'Misconfiguration',
                    'Accidental data exposure',
                    'Backup leaks',
                ],
                'resources' => 'Низкие',
                'possibility_level' => '3 (базовый)',
                'relevance' => 'Низкая-средняя',
            ],
        ];
    }

    /**
     * Get relevant threats from BDU
     */
    private function getRelevantThreats(): array
    {
        $threats = FstecThreat::relevant()
            ->orderBy('risk_level', 'desc')
            ->get()
            ->map(fn ($t) => [
                'fstec_id' => $t->fstec_id,
                'name' => $t->name,
                'type' => $t->threat_type,
                'class' => $t->threat_class,
                'probability' => $t->probability,
                'impact' => $t->impact,
                'risk_level' => $t->risk_level,
                'is_mitigated' => $t->is_mitigated,
                'affected_assets' => $t->affected_assets,
                'mitigation_measures' => $t->mitigation_measures,
            ])
            ->toArray();

        // Add CatVRF-specific threats not in BDU
        $catvrfSpecific = [
            [
                'fstec_id' => 'CATVRF-001',
                'name' => 'Cross-tenant data leakage through misconfigured scopes',
                'type' => 'НСД',
                'class' => 'internal',
                'probability' => 'medium',
                'impact' => 'high',
                'risk_level' => 'high',
                'is_mitigated' => true,
                'affected_assets' => ['tenant_databases', 'user_records'],
                'mitigation_measures' => ['TenantIsolationMiddleware', 'Global scopes', 'Row-level security'],
            ],
            [
                'fstec_id' => 'CATVRF-002',
                'name' => 'Biometric data leakage for deepfake/impersonation',
                'type' => 'Утечка',
                'class' => 'external',
                'probability' => 'medium',
                'impact' => 'very_high',
                'risk_level' => 'critical',
                'is_mitigated' => true,
                'affected_assets' => ['face_vectors', 'liveness_data', 'behavioral_profiles'],
                'mitigation_measures' => ['EncryptedCast', 'Column-level encryption', 'ConsentEngine', 'Audit logging'],
            ],
            [
                'fstec_id' => 'CATVRF-003',
                'name' => 'Insider data exfiltration via bulk export',
                'type' => 'НСД',
                'class' => 'internal',
                'probability' => 'medium',
                'impact' => 'high',
                'risk_level' => 'high',
                'is_mitigated' => true,
                'affected_assets' => ['customer_database', 'contact_lists'],
                'mitigation_measures' => ['ExportGuard', 'Rate limiting', 'InsiderThreatService', 'Audit logging'],
            ],
            [
                'fstec_id' => 'CATVRF-004',
                'name' => 'Behavioral biometrics scraping for profiling',
                'type' => 'Утечка',
                'class' => 'external',
                'probability' => 'low',
                'impact' => 'high',
                'risk_level' => 'medium',
                'is_mitigated' => true,
                'affected_assets' => ['behavioral_baselines', 'keystroke_patterns'],
                'mitigation_measures' => ['ConsentEngine', 'Data anonymization', 'Rate limiting', 'Bot detection'],
            ],
        ];

        return array_merge($threats, $catvrfSpecific);
    }

    /**
     * Get relevant vulnerabilities from BDU
     */
    private function getRelevantVulnerabilities(): array
    {
        return FstecVulnerability::relevant()
            ->orderBy('cvss_score', 'desc')
            ->limit(20)
            ->get()
            ->map(fn ($v) => [
                'fstec_id' => $v->fstec_id,
                'cve_id' => $v->cve_id,
                'name' => $v->name,
                'cvss_score' => $v->cvss_score,
                'severity' => $v->severity,
                'affected_products' => $v->affected_products,
                'is_patched' => $v->is_patched,
                'status' => $v->status,
                'days_since_disclosure' => $v->daysSinceDisclosure(),
                'is_stale' => $v->isStale(),
            ])
            ->toArray();
    }

    /**
     * Get risk matrix
     */
    private function getRiskMatrix(): array
    {
        $threats = FstecThreat::relevant()->get();
        
        $matrix = [
            'critical' => $threats->where('risk_level', 'critical')->count(),
            'high' => $threats->where('risk_level', 'high')->count(),
            'medium' => $threats->where('risk_level', 'medium')->count(),
            'low' => $threats->where('risk_level', 'low')->count(),
            'mitigated' => $threats->where('is_mitigated', true)->count(),
            'not_mitigated' => $threats->where('is_mitigated', false)->count(),
        ];

        return $matrix;
    }

    /**
     * Get protection level justification
     */
    private function getProtectionLevel(): array
    {
        return [
            'level' => 'УЗ-3',
            'justification' => [
                'subject_count' => '> 100 000 субъектов (Постановление №1119)',
                'data_categories' => [
                    'biometric_data' => 'Биометрические ПДн (Face ID, liveness, behavioral)',
                    'other_personal_data' => 'Иные ПДн в большом объёме',
                ],
                'threat_level' => 'Высокий уровень угроз от внешних и внутренних нарушителей',
                'potential_impact' => 'Критический ущерб при утечке биометрии',
            ],
            'reference' => 'Постановление Правительства РФ № 1119 от 01.11.2012',
        ];
    }

    /**
     * Get mitigation measures (FSTEC #21 groups)
     */
    private function getMitigationMeasures(): array
    {
        return [
            'group_1_identification' => [
                'measure' => 'Идентификация и аутентификация',
                'status' => 'implemented',
                'components' => ['Passkeys', '2FA', 'Behavioral Biometrics', 'userVerification'],
            ],
            'group_2_access_control' => [
                'measure' => 'Разграничение доступа',
                'status' => 'implemented',
                'components' => ['Spatie Permission', 'RoleIsolationService', 'Masked data for staff'],
            ],
            'group_3_access_management' => [
                'measure' => 'Управление доступом',
                'status' => 'implemented',
                'components' => ['RoleLimitService', 'InsiderThreatService', 'JIT access'],
            ],
            'group_4_audit' => [
                'measure' => 'Регистрация и учёт действий',
                'status' => 'implemented',
                'components' => ['PersonalDataAccessAudit', 'ClickHouse immutable logs'],
            ],
            'group_5_protection' => [
                'measure' => 'Защита от НСД',
                'status' => 'implemented',
                'components' => ['TenantIsolationMiddleware', 'Global scopes', 'ExportGuard'],
            ],
            'group_6_antivirus' => [
                'measure' => 'Антивирусная защита',
                'status' => 'implemented',
                'components' => ['ClamAV', 'ServerAntivirus'],
            ],
            'group_7_updates' => [
                'measure' => 'Обновление ПО',
                'status' => 'implemented',
                'components' => ['GitHub Actions', 'Dependabot', 'Snyk'],
            ],
            'group_8_backup' => [
                'measure' => 'Резервное копирование',
                'status' => 'implemented',
                'components' => ['EncryptedBackupJob', 'S3/Glacier'],
            ],
            'group_9_destruction' => [
                'measure' => 'Уничтожение ПДн',
                'status' => 'implemented',
                'components' => ['PurgePersonalDataJob', 'ConsentEngine'],
            ],
            'group_10_monitoring' => [
                'measure' => 'Контроль за действиями',
                'status' => 'implemented',
                'components' => ['BehavioralBiometrics', 'FraudControl'],
            ],
            'group_11_encryption' => [
                'measure' => 'Шифрование',
                'status' => 'implemented',
                'components' => ['EncryptedCast', 'AES-256-GCM', 'Key rotation'],
            ],
            'group_12_channels' => [
                'measure' => 'Защита каналов',
                'status' => 'implemented',
                'components' => ['TLS 1.3', 'HSTS', 'mTLS'],
            ],
            'group_13_tools' => [
                'measure' => 'Средства защиты информации',
                'status' => 'implemented',
                'components' => ['Cloudflare WAF', 'PostgreSQL Firewall', 'Suricata'],
            ],
            'group_14_segmentation' => [
                'measure' => 'Сегментация сети',
                'status' => 'implemented',
                'components' => ['VPC', 'Security Groups', 'Subnets'],
            ],
            'group_15_integrity' => [
                'measure' => 'Контроль целостности',
                'status' => 'implemented',
                'components' => ['AIDE', 'ConfigHashCheck'],
            ],
        ];
    }

    /**
     * Update threat model from BDU
     */
    public function updateFromBdu(): array
    {
        $this->logger->info('Updating threat model from BDU');

        $result = $this->bduService->syncAll();

        $this->logger->info('Threat model updated from BDU', [
            'result' => $result,
        ]);

        return $result;
    }

    /**
     * Generate threat model document for audit
     */
    public function generateAuditDocument(): string
    {
        $model = $this->getThreatModel();

        $document = "# Модель угроз ИСПДн CatVRF\n\n";
        $document .= "**Дата:** ".now()->format('d.m.Y')."\n";
        $document .= "**Уровень защищённости:** {$model['protection_level']['level']}\n\n";

        $document .= "## 1. Описание ИСПДн\n\n";
        $document .= "- **Система:** {$model['system_description']['system_name']}\n";
        $document .= "- **Тип:** {$model['system_description']['system_type']}\n";
        $document .= "- **Категории ПДн:** ".implode(', ', $model['system_description']['data_categories']['personal_data'])."\n";
        $document .= "- **Биометрические ПДн:** ".implode(', ', $model['system_description']['data_categories']['biometric_data'])."\n";
        $document .= "- **Субъектов:** {$model['system_description']['data_subjects_count']}\n";
        $document .= "- **Локализация:** {$model['system_description']['data_localization']}\n\n";

        $document .= "## 2. Модель нарушителя\n\n";
        foreach ($model['threat_actor_model'] as $type => $actor) {
            $document .= "### {$actor['type']}\n";
            $document .= "- **Мотивация:** {$actor['motivation']}\n";
            $document .= "- **Возможности:** ".implode(', ', $actor['capabilities'])."\n";
            $document .= "- **Уровень возможностей:** {$actor['possibility_level']}\n";
            if (isset($actor['relevance'])) {
                $document .= "- **Актуальность:** {$actor['relevance']}\n";
            }
            $document .= "\n";
        }

        $document .= "## 3. Актуальные угрозы\n\n";
        foreach ($model['relevant_threats'] as $threat) {
            $document .= "### {$threat['fstec_id']} - {$threat['name']}\n";
            $document .= "- **Тип:** {$threat['type']}\n";
            $document .= "- **Класс:** {$threat['class']}\n";
            $document .= "- **Вероятность:** {$threat['probability']}\n";
            $document .= "- **Последствия:** {$threat['impact']}\n";
            $document .= "- **Риск:** {$threat['risk_level']}\n";
            $document .= "- **Митигирован:** ".($threat['is_mitigated'] ? 'Да' : 'Нет')."\n";
            $document .= "\n";
        }

        $document .= "## 4. Матрица рисков\n\n";
        foreach ($model['risk_matrix'] as $level => $count) {
            $document .= "- **{$level}:** {$count}\n";
        }
        $document .= "\n";

        $document .= "## 5. Меры защиты (Приказ ФСТЭК №21)\n\n";
        foreach ($model['mitigation_measures'] as $group => $measure) {
            $document .= "- **{$measure['measure']}:** {$measure['status']}\n";
            $document .= "  - Компоненты: ".implode(', ', $measure['components'])."\n";
        }
        $document .= "\n";

        $document .= "---\n\n";
        $document .= "*Документ сгенерирован автоматически CatVRF ThreatModelService*\n";
        $document .= "*Последнее обновление: {$model['last_updated']}*\n";

        return $document;
    }

    /**
     * Check if threat model needs update
     */
    public function needsUpdate(): bool
    {
        $lastSync = FstecThreat::max('synced_at');
        
        if ($lastSync === null) {
            return true;
        }

        // Update if more than 30 days since last sync
        return $lastSync->diffInDays(now()) > 30;
    }
}
