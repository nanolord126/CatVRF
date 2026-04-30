<?php

declare(strict_types=1);

namespace App\Domains\Compliance\Services;

use App\Domains\Compliance\Models\ComplianceRecord;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Compliance Service - базовый сервис проверки соответствия требованиям
 *
 * Канон CatVRF 2026:
 * - FraudControlService::check() перед проверкой
 * - $this->db->transaction() для атомарности
 * - AuditService::record() для логирования
 * - correlation_id для трейсинга
 */
final readonly class ComplianceService
{
    public function __construct(private readonly DatabaseManager $db,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $audit,
        private readonly FraudControlService $fraud,) {}

    /**
     * Проверить соответствие требованию
     */
    public function checkRequirement(
        int $tenantId,
        string $requirementType,
        array $context = [],
        ?string $correlationId = null,
    ): array {
        $correlationId = $correlationId ?? Str::uuid()->toString();

        // Fraud check перед проверкой соответствия
        $this->fraud->check(
            userId: $tenantId,
            operationType: 'compliance_check',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use (
            $tenantId,
            $requirementType,
            $context,
            $correlationId,
        ) {
            $isCompliant = $this->evaluateRequirement($requirementType, $context);

            $record = ComplianceRecord::create([
                'tenant_id' => $tenantId,
                'requirement_type' => $requirementType,
                'status' => $isCompliant ? 'compliant' : 'non_compliant',
                'context' => $context,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->record(
                action: 'compliance_check',
                subjectType: ComplianceRecord::class,
                subjectId: $record->id,
                oldValues: [],
                newValues: [
                    'requirement_type' => $requirementType,
                    'status' => $record->status,
                ],
                correlationId: $correlationId,
            );

            $this->logger->$this->logger->info('Compliance check completed', [
                'tenant_id' => $tenantId,
                'requirement_type' => $requirementType,
                'status' => $record->status,
                'correlation_id' => $correlationId,
            ]);

            return [
                'compliant' => $isCompliant,
                'record_id' => $record->id,
                'requirement_type' => $requirementType,
            ];
        });
    }

    /**
     * Получить историю проверок соответствия
     */
    public function getHistory(int $tenantId, ?string $requirementType = null, int $limit = 100)
    {
        $query = ComplianceRecord::where('tenant_id', $tenantId);

        if ($requirementType) {
            $query->where('requirement_type', $requirementType);
        }

        return $query->orderBy('created_at', 'desc')->limit($limit)->get();
    }

    /**
     * Оценить требование (базовая логика)
     */
    private function evaluateRequirement(string $requirementType, array $context): bool
    {
        return match ($requirementType) {
            'gdpr_consent' => $context['consent_given'] ?? false,
            'kyc_verified' => $context['kyc_status'] === 'verified',
            'business_license' => ! empty($context['license_number']),
            'data_retention' => ($context['retention_days'] ?? 0) <= 365,
            default => true,
        };
    }
}
