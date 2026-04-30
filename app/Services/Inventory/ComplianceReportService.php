<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Cache\Repository as CacheRepository;
use Psr\Log\LoggerInterface;

/**
 * Compliance Report Service
 *
 * Generates compliance reports for regulators and auditors:
 * - 152-ФЗ compliance (Russian federal law on personal data)
 * - ФЗ-323 compliance (Healthcare law)
 * - ФЗ-61 compliance (Medicines circulation)
 * - Audit trail integrity reports
 * - Regulatory requirement checks
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class ComplianceReportService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
        private readonly CacheRepository $cache
    ) {}

    /**
     * Generate comprehensive compliance report
     *
     * @param  int  $tenantId  Tenant ID
     * @param  int  $warehouseId  Warehouse ID
     * @param  string  $reportPeriod  Report period (YYYY-MM)
     * @return array Compliance report
     */
    public function generateComplianceReport(int $tenantId, int $warehouseId, string $reportPeriod): array
    {
        $startDate = \Carbon\Carbon::parse($reportPeriod . '-01')->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $report = [
            'tenant_id' => $tenantId,
            'warehouse_id' => $warehouseId,
            'report_period' => $reportPeriod,
            'generated_at' => now()->toIso8601String(),
            'fz152_compliance' => $this->checkFZ152Compliance($tenantId, $startDate, $endDate),
            'fz323_compliance' => $this->checkFZ323Compliance($warehouseId, $startDate, $endDate),
            'fz61_compliance' => $this->checkFZ61Compliance($warehouseId, $startDate, $endDate),
            'audit_trail_integrity' => $this->checkAuditTrailIntegrity($warehouseId, $startDate, $endDate),
            'regulatory_findings' => $this->generateRegulatoryFindings($warehouseId, $startDate, $endDate),
        ];

        $this->logAction(
            action: 'compliance_report_generated',
            entityType: 'ComplianceReport',
            entityId: null,
            context: [
                'tenant_id' => $tenantId,
                'warehouse_id' => $warehouseId,
                'report_period' => $reportPeriod,
            ],
            userId: 0,
            tenantId: $tenantId
        );

        return $report;
    }

    /**
     * Check 152-ФЗ compliance (Personal Data Protection)
     *
     * @param  int  $tenantId  Tenant ID
     * @param  \Carbon\Carbon  $startDate  Start date
     * @param  \Carbon\Carbon  $endDate  End date
     * @return array Compliance status
     */
    private function checkFZ152Compliance(int $tenantId, \Carbon\Carbon $startDate, \Carbon\Carbon $endDate): array
    {
        $issues = [];

        $auditLogsWithPII = $this->db->table('audit_logs')
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->where('context', 'like', '%personal_data%')
            ->count();

        if ($auditLogsWithPII > 0) {
            $issues[] = [
                'severity' => 'high',
                'description' => 'Personal data found in audit logs',
                'count' => $auditLogsWithPII,
                'recommendation' => 'Ensure PII is anonymized in audit logs',
            ];
        }

        $usersWithoutConsent = $this->db->table('users')
            ->where('tenant_id', $tenantId)
            ->whereNull('data_processing_consent_at')
            ->count();

        if ($usersWithoutConsent > 0) {
            $issues[] = [
                'severity' => 'medium',
                'description' => 'Users without data processing consent',
                'count' => $usersWithoutConsent,
                'recommendation' => 'Obtain consent from all users',
            ];
        }

        $isCompliant = empty($issues);

        return [
            'law' => '152-ФЗ',
            'description' => 'Federal Law on Personal Data',
            'is_compliant' => $isCompliant,
            'compliance_percentage' => $isCompliant ? 100 : max(0, 100 - (count($issues) * 25)),
            'issues' => $issues,
            'checked_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Check ФЗ-323 compliance (Healthcare)
     *
     * @param  int  $warehouseId  Warehouse ID
     * @param  \Carbon\Carbon  $startDate  Start date
     * @param  \Carbon\Carbon  $endDate  End date
     * @return array Compliance status
     */
    private function checkFZ323Compliance(int $warehouseId, \Carbon\Carbon $startDate, \Carbon\Carbon $endDate): array
    {
        $issues = [];

        $pharmaceuticalBatches = $this->db->table('inventory_batches as ib')
            ->join('products as p', 'ib.product_id', '=', 'p.id')
            ->where('ib.warehouse_id', $warehouseId)
            ->where('p.category', 'like', '%pharmaceutical%')
            ->get();

        foreach ($pharmaceuticalBatches as $batch) {
            if (! $batch->serial_number) {
                $issues[] = [
                    'severity' => 'critical',
                    'description' => 'Pharmaceutical batch without serial number',
                    'batch_id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'recommendation' => 'All pharmaceutical products must have serial numbers',
                ];
            }

            if (! $batch->expiry_date) {
                $issues[] = [
                    'severity' => 'critical',
                    'description' => 'Pharmaceutical batch without expiry date',
                    'batch_id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'recommendation' => 'All pharmaceutical products must have expiry dates',
                ];
            }
        }

        $warehouse = $this->db->table('warehouses')
            ->where('id', $warehouseId)
            ->first();

        if ($warehouse && $warehouse->type === 'pharmaceutical') {
            $hasLicense = $this->db->table('warehouse_licenses')
                ->where('warehouse_id', $warehouseId)
                ->where('license_type', 'pharmaceutical')
                ->where('valid_until', '>', now())
                ->exists();

            if (! $hasLicense) {
                $issues[] = [
                    'severity' => 'critical',
                    'description' => 'Pharmaceutical warehouse without valid license',
                    'recommendation' => 'Obtain valid pharmaceutical license',
                ];
            }
        }

        $isCompliant = empty($issues);

        return [
            'law' => 'ФЗ-323',
            'description' => 'Federal Law on Healthcare',
            'is_compliant' => $isCompliant,
            'compliance_percentage' => $isCompliant ? 100 : max(0, 100 - (count($issues) * 20)),
            'issues' => $issues,
            'checked_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Check ФЗ-61 compliance (Medicines Circulation)
     *
     * @param  int  $warehouseId  Warehouse ID
     * @param  \Carbon\Carbon  $startDate  Start date
     * @param  \Carbon\Carbon  $endDate  End date
     * @return array Compliance status
     */
    private function checkFZ61Compliance(int $warehouseId, \Carbon\Carbon $startDate, \Carbon\Carbon $endDate): array
    {
        $issues = [];

        $expiredBatches = $this->db->table('inventory_batches')
            ->where('warehouse_id', $warehouseId)
            ->where('expiry_date', '<', now())
            ->where('status', '!=', 'recalled')
            ->where('status', '!=', 'blocked')
            ->count();

        if ($expiredBatches > 0) {
            $issues[] = [
                'severity' => 'critical',
                'description' => 'Expired batches not recalled or blocked',
                'count' => $expiredBatches,
                'recommendation' => 'Immediately recall or block all expired batches',
            ];
        }

        $batchesWithoutTraceability = $this->db->table('inventory_batches')
            ->where('warehouse_id', $warehouseId)
            ->whereNull('manufacture_date')
            ->count();

        if ($batchesWithoutTraceability > 0) {
            $issues[] = [
                'severity' => 'high',
                'description' => 'Batches without manufacture date',
                'count' => $batchesWithoutTraceability,
                'recommendation' => 'Record manufacture date for all batches',
            ];
        }

        $quarantineBatches = $this->db->table('inventory_batches')
            ->where('warehouse_id', $warehouseId)
            ->where('status', 'quarantine')
            ->get();

        foreach ($quarantineBatches as $batch) {
            $daysInQuarantine = now()->diffInDays($batch->quarantine_started_at ?? now());

            if ($daysInQuarantine > 30) {
                $issues[] = [
                    'severity' => 'medium',
                    'description' => 'Batch in quarantine for more than 30 days',
                    'batch_id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'days_in_quarantine' => $daysInQuarantine,
                    'recommendation' => 'Review and resolve quarantine status',
                ];
            }
        }

        $recallsWithoutResolution = $this->db->table('batch_recalls')
            ->join('inventory_batches', 'batch_recalls.batch_id', '=', 'inventory_batches.id')
            ->where('inventory_batches.warehouse_id', $warehouseId)
            ->where('batch_recalls.status', 'active')
            ->where('batch_recalls.created_at', '<', now()->subDays(30))
            ->count();

        if ($recallsWithoutResolution > 0) {
            $issues[] = [
                'severity' => 'critical',
                'description' => 'Active recalls unresolved for more than 30 days',
                'count' => $recallsWithoutResolution,
                'recommendation' => 'Resolve all active recalls immediately',
            ];
        }

        $isCompliant = empty($issues);

        return [
            'law' => 'ФЗ-61',
            'description' => 'Federal Law on Medicines Circulation',
            'is_compliant' => $isCompliant,
            'compliance_percentage' => $isCompliant ? 100 : max(0, 100 - (count($issues) * 15)),
            'issues' => $issues,
            'checked_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Check audit trail integrity
     *
     * @param  int  $warehouseId  Warehouse ID
     * @param  \Carbon\Carbon  $startDate  Start date
     * @param  \Carbon\Carbon  $endDate  End date
     * @return array Integrity status
     */
    private function checkAuditTrailIntegrity(int $warehouseId, \Carbon\Carbon $startDate, \Carbon\Carbon $endDate): array
    {
        $issues = [];

        $itemIds = $this->db->table('inventory_items')
            ->where('warehouse_id', $warehouseId)
            ->pluck('id')
            ->toArray();

        $stockMovements = $this->db->table('stock_movements')
            ->whereIn('inventory_item_id', $itemIds)
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->get();

        $movementsWithoutCorrelationId = $stockMovements->whereNull('correlation_id')->count();

        if ($movementsWithoutCorrelationId > 0) {
            $issues[] = [
                'severity' => 'medium',
                'description' => 'Stock movements without correlation ID',
                'count' => $movementsWithoutCorrelationId,
                'recommendation' => 'Ensure all movements have correlation IDs for traceability',
            ];
        }

        $movementsWithoutUser = $stockMovements->whereNull('created_by')->count();

        if ($movementsWithoutUser > 0) {
            $issues[] = [
                'severity' => 'high',
                'description' => 'Stock movements without user attribution',
                'count' => $movementsWithoutUser,
                'recommendation' => 'All movements must be attributed to a user',
            ];
        }

        $auditLogs = $this->db->table('audit_logs')
            ->whereIn('entity_id', $itemIds)
            ->where('entity_type', 'InventoryItem')
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->get();

        $logsWithoutUser = $auditLogs->whereNull('user_id')->count();

        if ($logsWithoutUser > 0) {
            $issues[] = [
                'severity' => 'medium',
                'description' => 'Audit logs without user attribution',
                'count' => $logsWithoutUser,
                'recommendation' => 'Ensure all audit logs have user attribution',
            ];
        }

        $isCompliant = empty($issues);

        return [
            'category' => 'Audit Trail Integrity',
            'is_compliant' => $isCompliant,
            'compliance_percentage' => $isCompliant ? 100 : max(0, 100 - (count($issues) * 20)),
            'issues' => $issues,
            'checked_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Generate regulatory findings
     *
     * @param  int  $warehouseId  Warehouse ID
     * @param  \Carbon\Carbon  $startDate  Start date
     * @param  \Carbon\Carbon  $endDate  End date
     * @return array Findings
     */
    private function generateRegulatoryFindings(int $warehouseId, \Carbon\Carbon $startDate, \Carbon\Carbon $endDate): array
    {
        $findings = [];

        $totalBatches = $this->db->table('inventory_batches')
            ->where('warehouse_id', $warehouseId)
            ->count();

        $recalledBatches = $this->db->table('inventory_batches')
            ->where('warehouse_id', $warehouseId)
            ->where('status', 'recalled')
            ->count();

        $blockedBatches = $this->db->table('inventory_batches')
            ->where('warehouse_id', $warehouseId)
            ->where('status', 'blocked')
            ->count();

        $quarantineBatches = $this->db->table('inventory_batches')
            ->where('warehouse_id', $warehouseId)
            ->where('status', 'quarantine')
            ->count();

        if ($recalledBatches > 0) {
            $findings[] = [
                'type' => 'recall',
                'severity' => 'critical',
                'description' => sprintf('%d batches under recall', $recalledBatches),
                'percentage' => $totalBatches > 0 ? ($recalledBatches / $totalBatches) * 100 : 0,
            ];
        }

        if ($blockedBatches > 0) {
            $findings[] = [
                'type' => 'blocked',
                'severity' => 'high',
                'description' => sprintf('%d batches blocked', $blockedBatches),
                'percentage' => $totalBatches > 0 ? ($blockedBatches / $totalBatches) * 100 : 0,
            ];
        }

        if ($quarantineBatches > 0) {
            $findings[] = [
                'type' => 'quarantine',
                'severity' => 'medium',
                'description' => sprintf('%d batches in quarantine', $quarantineBatches),
                'percentage' => $totalBatches > 0 ? ($quarantineBatches / $totalBatches) * 100 : 0,
            ];
        }

        return [
            'total_findings' => count($findings),
            'findings' => $findings,
            'summary' => [
                'total_batches' => $totalBatches,
                'recalled_batches' => $recalledBatches,
                'blocked_batches' => $blockedBatches,
                'quarantine_batches' => $quarantineBatches,
            ],
        ];
    }

    /**
     * Export compliance report to PDF-ready format
     *
     * @param  int  $tenantId  Tenant ID
     * @param  int  $warehouseId  Warehouse ID
     * @param  string  $reportPeriod  Report period
     * @return array Report data
     */
    public function exportForPdf(int $tenantId, int $warehouseId, string $reportPeriod): array
    {
        $report = $this->generateComplianceReport($tenantId, $warehouseId, $reportPeriod);

        return [
            'title' => 'Compliance Report',
            'period' => $reportPeriod,
            'warehouse_id' => $warehouseId,
            'generated_at' => $report['generated_at'],
            'overall_compliance' => $this->calculateOverallCompliance($report),
            'sections' => [
                [
                    'title' => '152-ФЗ Compliance',
                    'data' => $report['fz152_compliance'],
                ],
                [
                    'title' => 'ФЗ-323 Compliance',
                    'data' => $report['fz323_compliance'],
                ],
                [
                    'title' => 'ФЗ-61 Compliance',
                    'data' => $report['fz61_compliance'],
                ],
                [
                    'title' => 'Audit Trail Integrity',
                    'data' => $report['audit_trail_integrity'],
                ],
                [
                    'title' => 'Regulatory Findings',
                    'data' => $report['regulatory_findings'],
                ],
            ],
        ];
    }

    /**
     * Calculate overall compliance percentage
     *
     * @param  array  $report  Compliance report
     * @return float Overall percentage
     */
    private function calculateOverallCompliance(array $report): float
    {
        $sections = [
            $report['fz152_compliance']['compliance_percentage'],
            $report['fz323_compliance']['compliance_percentage'],
            $report['fz61_compliance']['compliance_percentage'],
            $report['audit_trail_integrity']['compliance_percentage'],
        ];

        return array_sum($sections) / count($sections);
    }

    /**
     * Generate compliance checklist
     *
     * @param  int  $warehouseId  Warehouse ID
     * @return array Checklist
     */
    public function generateComplianceChecklist(int $warehouseId): array
    {
        $checklist = [
            'fz152' => [
                'title' => '152-ФЗ Checklist',
                'items' => [
                    [
                        'id' => 'fz152_1',
                        'description' => 'Personal data anonymized in logs',
                        'status' => 'pending',
                        'evidence_required' => true,
                    ],
                    [
                        'id' => 'fz152_2',
                        'description' => 'User consent obtained',
                        'status' => 'pending',
                        'evidence_required' => true,
                    ],
                    [
                        'id' => 'fz152_3',
                        'description' => 'Data retention policy implemented',
                        'status' => 'pending',
                        'evidence_required' => true,
                    ],
                ],
            ],
            'fz323' => [
                'title' => 'ФЗ-323 Checklist',
                'items' => [
                    [
                        'id' => 'fz323_1',
                        'description' => 'Pharmaceutical license valid',
                        'status' => 'pending',
                        'evidence_required' => true,
                    ],
                    [
                        'id' => 'fz323_2',
                        'description' => 'Serial numbers for all pharmaceutical products',
                        'status' => 'pending',
                        'evidence_required' => true,
                    ],
                    [
                        'id' => 'fz323_3',
                        'description' => 'Expiry dates for all pharmaceutical products',
                        'status' => 'pending',
                        'evidence_required' => true,
                    ],
                ],
            ],
            'fz61' => [
                'title' => 'ФЗ-61 Checklist',
                'items' => [
                    [
                        'id' => 'fz61_1',
                        'description' => 'No expired batches available',
                        'status' => 'pending',
                        'evidence_required' => true,
                    ],
                    [
                        'id' => 'fz61_2',
                        'description' => 'Manufacture dates recorded',
                        'status' => 'pending',
                        'evidence_required' => true,
                    ],
                    [
                        'id' => 'fz61_3',
                        'description' => 'Recall process documented',
                        'status' => 'pending',
                        'evidence_required' => true,
                    ],
                    [
                        'id' => 'fz61_4',
                        'description' => 'Quarantine procedures followed',
                        'status' => 'pending',
                        'evidence_required' => true,
                    ],
                ],
            ],
        ];

        return [
            'warehouse_id' => $warehouseId,
            'checklist' => $checklist,
            'total_items' => array_sum(array_map(fn ($section) => count($section['items']), $checklist)),
        ];
    }
}
