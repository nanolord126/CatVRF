<?php

declare(strict_types=1);

namespace App\Services\Compliance;

use App\Models\FstecThreat;
use App\Models\FstecVulnerability;
use App\Models\User;
use App\Models\UserConsent;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;

/**
 * Personal Data Compliance Service
 * 
 * Checks CatVRF readiness for Roskomnadzor/FSTEC audits.
 * Verifies all 152-FZ and FSTEC #21 requirements are met.
 * 
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 * CRITICAL: Use before every audit to ensure compliance.
 */
final readonly class PersonalDataComplianceService
{
    use WithAuditLogging;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ThreatModelService $threatModel,
        private readonly DatabaseManager $db,
        private readonly AuditService $audit,
    ) {}

    /**
     * Run full compliance check
     */
    public function checkCompliance(): array
    {
        $this->logger->info('Running full compliance check');

        $results = [
            'overall_status' => 'pending',
            'checks' => [],
            'critical_issues' => [],
            'warnings' => [],
            'recommendations' => [],
            'score' => 0,
        ];

        // Run all compliance checks
        $results['checks']['protection_level'] = $this->checkProtectionLevel();
        $results['checks']['fstec_measures'] = $this->checkFstecMeasures();
        $results['checks']['consent_engine'] = $this->checkConsentEngine();
        $results['checks']['biometric_consent'] = $this->checkBiometricConsent();
        $results['checks']['data_encryption'] = $this->checkDataEncryption();
        $results['checks']['audit_logging'] = $this->checkAuditLogging();
        $results['checks']['data_localization'] = $this->checkDataLocalization();
        $results['checks']['threat_model'] = $this->checkThreatModel();
        $results['checks']['vulnerability_management'] = $this->checkVulnerabilityManagement();
        $results['checks']['data_destruction'] = $this->checkDataDestruction();
        $results['checks']['documentation'] = $this->checkDocumentation();

        // Calculate overall score
        $totalChecks = count($results['checks']);
        $passedChecks = 0;
        foreach ($results['checks'] as $check) {
            if ($check['status'] === 'passed') {
                $passedChecks++;
            }
        }
        $results['score'] = ($passedChecks / $totalChecks) * 100;

        // Determine overall status
        $results['overall_status'] = match (true) {
            $results['score'] === 100 => 'compliant',
            $results['score'] >= 90 => 'minor_issues',
            $results['score'] >= 70 => 'needs_attention',
            default => 'non_compliant',
        };

        // Collect critical issues and warnings
        foreach ($results['checks'] as $name => $check) {
            if ($check['status'] === 'failed') {
                $results['critical_issues'][] = [
                    'check' => $name,
                    'message' => $check['message'],
                ];
            } elseif ($check['status'] === 'warning') {
                $results['warnings'][] = [
                    'check' => $name,
                    'message' => $check['message'],
                ];
            }

            if (! empty($check['recommendations'])) {
                $results['recommendations'] = array_merge(
                    $results['recommendations'],
                    $check['recommendations']
                );
            }
        }

        $this->logger->info('Compliance check completed', [
            'overall_status' => $results['overall_status'],
            'score' => $results['score'],
            'critical_issues' => count($results['critical_issues']),
            'warnings' => count($results['warnings']),
        ]);

        return $results;
    }

    /**
     * Check protection level (УЗ-3)
     */
    private function checkProtectionLevel(): array
    {
        $configuredLevel = config('ispdn.protection_level', 3);
        $requiredLevel = 3; // УЗ-3 for biometric data

        if ($configuredLevel < $requiredLevel) {
            return [
                'status' => 'failed',
                'message' => "Protection level is УЗ-{$configuredLevel}, required УЗ-{$requiredLevel}",
                'recommendations' => [
                    'Update config/ispdn.php to set protection_level to 3',
                ],
            ];
        }

        return [
            'status' => 'passed',
            'message' => "Protection level correctly set to УЗ-{$configuredLevel}",
        ];
    }

    /**
     * Check FSTEC #21 measures implementation
     */
    private function checkFstecMeasures(): array
    {
        $measures = config('ispdn.fstec21_measures', []);
        $requiredGroups = range(1, 15);

        $missingGroups = [];
        foreach ($requiredGroups as $group) {
            if (! isset($measures["group_{$group}"])) {
                $missingGroups[] = $group;
            }
        }

        if (! empty($missingGroups)) {
            return [
                'status' => 'failed',
                'message' => 'Missing FSTEC #21 measure groups: '.implode(', ', $missingGroups),
                'recommendations' => [
                    'Implement missing FSTEC #21 measure groups',
                    'Update config/ispdn.php with all 15 groups',
                ],
            ];
        }

        return [
            'status' => 'passed',
            'message' => 'All 15 FSTEC #21 measure groups configured',
        ];
    }

    /**
     * Check consent engine functionality
     */
    private function checkConsentEngine(): array
    {
        try {
            // Check if consent records exist
            $consentCount = UserConsent::count();

            if ($consentCount === 0) {
                return [
                    'status' => 'warning',
                    'message' => 'No consent records found in database',
                    'recommendations' => [
                        'Ensure ConsentEngine is being used for all personal data processing',
                        'Seed test consent records for development',
                    ],
                ];
            }

            // Check if active consents exist
            $activeConsents = UserConsent::active()->count();
            if ($activeConsents === 0 && $consentCount > 0) {
                return [
                    'status' => 'warning',
                    'message' => 'No active consent records found',
                    'recommendations' => [
                        'Check consent expiration dates',
                        'Ensure users are granting consent',
                    ],
                ];
            }

            return [
                'status' => 'passed',
                'message' => "Consent engine operational ({$consentCount} total, {$activeConsents} active)",
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'failed',
                'message' => 'Consent engine check failed: '.$e->getMessage(),
                'recommendations' => [
                    'Check database connection',
                    'Verify user_consents table exists',
                ],
            ];
        }
    }

    /**
     * Check biometric consent requirements
     */
    private function checkBiometricConsent(): array
    {
        try {
            $biometricConsents = UserConsent::biometric()->active()->count();

            if ($biometricConsents === 0) {
                return [
                    'status' => 'warning',
                    'message' => 'No active biometric consent records found',
                    'recommendations' => [
                        'Ensure biometric consent forms are being used',
                        'Verify signature_method is set to "ukep" or "written"',
                    ],
                ];
            }

            return [
                'status' => 'passed',
                'message' => "Biometric consent requirements met ({$biometricConsents} active)",
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'failed',
                'message' => 'Biometric consent check failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Check data encryption
     */
    private function checkDataEncryption(): array
    {
        $encryptionEnabled = config('ispdn.encryption.enabled', false);
        $columnLevelEnabled = config('ispdn.encryption.column_level.enabled', false);

        if (! $encryptionEnabled) {
            return [
                'status' => 'failed',
                'message' => 'Data encryption is not enabled',
                'recommendations' => [
                    'Enable encryption in config/ispdn.php',
                    'Configure APP_KEY in .env',
                ],
            ];
        }

        if (! $columnLevelEnabled) {
            return [
                'status' => 'warning',
                'message' => 'Column-level encryption is not enabled',
                'recommendations' => [
                    'Enable column-level encryption for sensitive fields',
                    'Use EncryptedCast for biometric data',
                ],
            ];
        }

        return [
            'status' => 'passed',
            'message' => 'Data encryption properly configured',
        ];
    }

    /**
     * Check audit logging
     */
    private function checkAuditLogging(): array
    {
        $clickhouseEnabled = config('audit.use_clickhouse', true);
        $fallbackEnabled = config('audit.use_fallback', true);

        if (! $clickhouseEnabled && ! $fallbackEnabled) {
            return [
                'status' => 'failed',
                'message' => 'Audit logging is not configured',
                'recommendations' => [
                    'Enable ClickHouse or MySQL fallback for audit logging',
                    'Configure ClickHouse connection in config/database.php',
                ],
            ];
        }

        try {
            // Check if audit table exists
            if ($this->db->connection()->getSchemaBuilder()->hasTable('personal_data_audit_logs')) {
                return [
                    'status' => 'passed',
                    'message' => 'Audit logging configured and operational',
                ];
            }

            return [
                'status' => 'warning',
                'message' => 'Audit log table not found',
                'recommendations' => [
                    'Run migration: php artisan migrate',
                    'Ensure 2026_04_23_000007_create_personal_data_audit_logs_table.php exists',
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'failed',
                'message' => 'Audit logging check failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Check data localization (RF requirement)
     */
    private function checkDataLocalization(): array
    {
        $localized = config('ispdn.data_localization', 'russia');

        if (strtolower($localized) !== 'russia') {
            return [
                'status' => 'failed',
                'message' => 'Data is not localized in Russian Federation',
                'recommendations' => [
                    'Ensure all personal data is stored in RF',
                    'Update config/ispdn.php',
                    'Verify database servers are in RF',
                ],
            ];
        }

        return [
            'status' => 'passed',
            'message' => 'Data localization requirement met',
        ];
    }

    /**
     * Check threat model
     */
    private function checkThreatModel(): array
    {
        try {
            $threatsCount = FstecThreat::relevant()->count();
            $notMitigated = FstecThreat::relevant()->notMitigated()->count();

            if ($threatsCount === 0) {
                return [
                    'status' => 'warning',
                    'message' => 'No relevant threats found in threat model',
                    'recommendations' => [
                        'Run SyncFstecBduJob to sync threats from BDU',
                        'Add CatVRF-specific threats manually',
                    ],
                ];
            }

            if ($notMitigated > 0) {
                return [
                    'status' => 'warning',
                    'message' => "{$notMitigated} relevant threats are not mitigated",
                    'recommendations' => [
                        'Review and implement mitigation measures',
                        'Update threat model documentation',
                    ],
                ];
            }

            return [
                'status' => 'passed',
                'message' => "Threat model up-to-date ({$threatsCount} threats, all mitigated)",
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'failed',
                'message' => 'Threat model check failed: '.$e->getMessage(),
                'recommendations' => [
                    'Run migrations for fstec_threats table',
                    'Check ThreatModelService configuration',
                ],
            ];
        }
    }

    /**
     * Check vulnerability management
     */
    private function checkVulnerabilityManagement(): array
    {
        try {
            $vulnerabilitiesCount = FstecVulnerability::relevant()->count();
            $notPatched = FstecVulnerability::relevant()->notPatched()->count();
            $critical = FstecVulnerability::relevant()->critical()->count();

            if ($vulnerabilitiesCount === 0) {
                return [
                    'status' => 'warning',
                    'message' => 'No vulnerabilities tracked',
                    'recommendations' => [
                        'Run SyncFstecBduJob to sync vulnerabilities from BDU',
                    ],
                ];
            }

            if ($critical > 0) {
                return [
                    'status' => 'failed',
                    'message' => "{$critical} critical vulnerabilities are not patched",
                    'recommendations' => [
                        'Patch critical vulnerabilities immediately',
                        'Review CVSS scores and impact',
                    ],
                ];
            }

            if ($notPatched > 0) {
                return [
                    'status' => 'warning',
                    'message' => "{$notPatched} vulnerabilities are not patched",
                    'recommendations' => [
                        'Review and patch vulnerabilities',
                        'Implement mitigation measures if patching is not possible',
                    ],
                ];
            }

            return [
                'status' => 'passed',
                'message' => "Vulnerability management operational ({$vulnerabilitiesCount} tracked, all patched)",
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'warning',
                'message' => 'Vulnerability management check failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Check data destruction process
     */
    private function checkDataDestruction(): array
    {
        try {
            $pendingPurge = UserConsent::pendingPurge()->count();

            if ($pendingPurge > 100) {
                return [
                    'status' => 'warning',
                    'message' => "{$pendingPurge} consent withdrawals waiting for data destruction",
                    'recommendations' => [
                        'Check if PurgePersonalDataJob is running',
                        'Verify queue worker is operational',
                    ],
                ];
            }

            return [
                'status' => 'passed',
                'message' => 'Data destruction process operational',
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'failed',
                'message' => 'Data destruction check failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Check documentation readiness
     */
    private function checkDocumentation(): array
    {
        $requiredDocs = [
            'privacy_policy' => file_exists(base_path('docs/compliance/152-fz/privacy_policy_template.md')),
            'consent_form' => file_exists(base_path('docs/compliance/152-fz/consent_form_template.md')),
            'biometric_consent' => file_exists(base_path('docs/compliance/152-fz/biometric_consent_form_template.md')),
            'protection_level_act' => file_exists(base_path('docs/compliance/152-fz/protection_level_act_template.md')),
            'compliance_checklist' => file_exists(base_path('docs/compliance/152-fz/COMPLIANCE_CHECKLIST.md')),
        ];

        $missing = array_filter($requiredDocs, fn ($exists) => ! $exists);

        if (! empty($missing)) {
            return [
                'status' => 'warning',
                'message' => 'Missing documentation: '.implode(', ', array_keys($missing)),
                'recommendations' => [
                    'Create missing document templates',
                    'Review docs/compliance/152-fz/ directory',
                ],
            ];
        }

        return [
            'status' => 'passed',
            'message' => 'All required documentation templates exist',
        ];
    }

    /**
     * Generate compliance report for audit
     */
    public function generateAuditReport(): string
    {
        $results = $this->checkCompliance();
        $threatModel = $this->threatModel->getThreatModel();

        $report = "# CatVRF Compliance Report\n\n";
        $report .= "**Date:** ".now()->format('d.m.Y H:i')."\n";
        $report .= "**Overall Status:** ".strtoupper($results['overall_status'])."\n";
        $report .= "**Compliance Score:** {$results['score']}%\n\n";

        $report .= "## Executive Summary\n\n";
        $report .= "- **Passed Checks:** ".count(array_filter($results['checks'], fn ($c) => $c['status'] === 'passed'))."\n";
        $report .= "- **Failed Checks:** ".count(array_filter($results['checks'], fn ($c) => $c['status'] === 'failed'))."\n";
        $report .= "- **Warnings:** ".count(array_filter($results['checks'], fn ($c) => $c['status'] === 'warning'))."\n";
        $report .= "- **Critical Issues:** ".count($results['critical_issues'])."\n\n";

        if (! empty($results['critical_issues'])) {
            $report .= "## Critical Issues\n\n";
            foreach ($results['critical_issues'] as $issue) {
                $report .= "- **{$issue['check']}:** {$issue['message']}\n";
            }
            $report .= "\n";
        }

        if (! empty($results['warnings'])) {
            $report .= "## Warnings\n\n";
            foreach ($results['warnings'] as $warning) {
                $report .= "- **{$warning['check']}:** {$warning['message']}\n";
            }
            $report .= "\n";
        }

        $report .= "## Detailed Checks\n\n";
        foreach ($results['checks'] as $name => $check) {
            $statusIcon = match ($check['status']) {
                'passed' => '✅',
                'warning' => '⚠️',
                'failed' => '❌',
            };
            $report .= "{$statusIcon} **{$name}:** {$check['message']}\n";
        }
        $report .= "\n";

        $report .= "## Threat Model Summary\n\n";
        $report .= "- **Protection Level:** {$threatModel['protection_level']['level']}\n";
        $report .= "- **Relevant Threats:** ".count($threatModel['relevant_threats'])."\n";
        $report .= "- **Relevant Vulnerabilities:** ".count($threatModel['relevant_vulnerabilities'])."\n";
        $report .= "- **Last Updated:** {$threatModel['last_updated']}\n\n";

        if (! empty($results['recommendations'])) {
            $report .= "## Recommendations\n\n";
            foreach ($results['recommendations'] as $rec) {
                $report .= "- {$rec}\n";
            }
            $report .= "\n";
        }

        $report .= "---\n\n";
        $report .= "*Report generated by PersonalDataComplianceService*\n";

        return $report;
    }

    /**
     * Get audit checklist for Roskomnadzor
     */
    public function getAuditChecklist(): array
    {
        return [
            'documents' => [
                'Уведомление в реестр операторов ПДн',
                'Акт определения уровня защищённости (УЗ-3)',
                'Политика обработки персональных данных',
                'Приказ о назначении ответственного за обработку ПДн',
                'Формы согласий на обработку ПДн (в т.ч. биометрических)',
                'Регламент защиты ИСПДн',
                'Модель угроз ИСПДн',
                'Акт внутреннего аудита',
            ],
            'technical_measures' => [
                'Шифрование данных (at-rest + in-transit)',
                'Идентификация и аутентификация (Passkeys + Behavioral)',
                'Разграничение доступа (Role-based + JIT)',
                'Регистрация действий (Immutable ClickHouse audit)',
                'Локализация данных в РФ',
                'Защита от НСД (Tenant isolation + WAF)',
                'Уничтожение ПДн при отзыве согласия (30 дней)',
            ],
            'evidence' => [
                'Логи ClickHouse за последний год',
                'Записи согласий с УКЭП/письменной подписью',
                'Акты уничтожения ПДн',
                'Сертификаты шифрования',
                'Документы о локализации серверов',
            ],
        ];
    }
}
