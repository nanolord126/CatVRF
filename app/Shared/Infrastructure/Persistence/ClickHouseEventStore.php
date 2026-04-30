<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence;

use Carbon\CarbonImmutable;

use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Smalot\ClickHouse\Client as ClickHouseClient;

/**
 * ClickHouse Event Store for Medical and Financial Events
 * Provides long-term storage and analytics capabilities
 */
final readonly class ClickHouseEventStore
{
    private readonly ClickHouseClient $client;

    public function __construct(
        private readonly LogManager $log,
    )
    {
        $this->client = app('clickhouse');
    }

    public function storeEvent(array $eventData): bool
    {
        try {
            $this->client->insert('event_store', [
                [
                    'event_id' => $eventData['event_id'] ?? Str::uuid()->toString(),
                    'event_type' => $eventData['event_type'],
                    'event_name' => $eventData['event_name'] ?? $eventData['event_type'],
                    'payload' => json_encode($eventData['payload']),
                    'payload_hash' => hash('sha256', json_encode($eventData['payload'])),
                    'correlation_id' => $eventData['correlation_id'] ?? null,
                    'causation_id' => $eventData['causation_id'] ?? null,
                    'user_id' => $eventData['user_id'] ?? null,
                    'tenant_id' => $eventData['tenant_id'] ?? null,
                    'vertical' => $eventData['vertical'] ?? 'unknown',
                    'is_medical' => $eventData['is_medical'] ?? false,
                    'is_financial' => $eventData['is_financial'] ?? false,
                    'is_emergency' => $eventData['is_emergency'] ?? false,
                    'data_classification' => $eventData['data_classification'] ?? 'public',
                    'occurred_at' => $eventData['occurred_at'] ?? CarbonImmutable::now()->toDateTimeString(),
                    'published_at' => $eventData['published_at'] ?? CarbonImmutable::now()->toDateTimeString(),
                    'processed_at' => $eventData['processed_at'] ?? null,
                    'source' => $eventData['source'] ?? 'outbox',
                    'publisher' => $eventData['publisher'] ?? 'system',
                    'processing_time_ms' => $eventData['processing_time_ms'] ?? null,
                    'status' => $eventData['status'] ?? 'published',
                ],
            ]);

            // Store in specialized tables if applicable
            if ($eventData['is_medical'] ?? false) {
                $this->storeMedicalEvent($eventData);
            }

            if ($eventData['is_financial'] ?? false) {
                $this->storeFinancialEvent($eventData);
            }

            return true;

        } catch (\Throwable $e) {
            $this->log->error('Failed to store event in ClickHouse', [
                'event_type' => $eventData['event_type'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function storeMedicalEvent(array $eventData): bool
    {
        try {
            $this->client->insert('event_store_medical', [
                [
                    'event_id' => $eventData['event_id'] ?? Str::uuid()->toString(),
                    'event_type' => $eventData['event_type'],
                    'payload' => json_encode($eventData['payload']),
                    'correlation_id' => $eventData['correlation_id'] ?? null,
                    'user_id' => $eventData['user_id'] ?? null,
                    'patient_id' => $eventData['patient_id'] ?? $eventData['user_id'] ?? null,
                    'medical_record_id' => $eventData['medical_record_id'] ?? null,
                    'occurred_at' => $eventData['occurred_at'] ?? CarbonImmutable::now()->toDateTimeString(),
                    'pii_masked' => $eventData['pii_masked'] ?? true,
                    'pii_fields' => $eventData['pii_fields'] ?? [],
                ],
            ]);

            return true;

        } catch (\Throwable $e) {
            $this->log->error('Failed to store medical event in ClickHouse', [
                'event_type' => $eventData['event_type'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function storeFinancialEvent(array $eventData): bool
    {
        try {
            $this->client->insert('event_store_financial', [
                [
                    'event_id' => $eventData['event_id'] ?? Str::uuid()->toString(),
                    'event_type' => $eventData['event_type'],
                    'payload' => json_encode($eventData['payload']),
                    'correlation_id' => $eventData['correlation_id'] ?? null,
                    'user_id' => $eventData['user_id'] ?? null,
                    'account_id' => $eventData['account_id'] ?? null,
                    'transaction_id' => $eventData['transaction_id'] ?? null,
                    'amount' => $eventData['amount'] ?? null,
                    'currency' => $eventData['currency'] ?? 'RUB',
                    'occurred_at' => $eventData['occurred_at'] ?? CarbonImmutable::now()->toDateTimeString(),
                    'requires_audit' => $eventData['requires_audit'] ?? false,
                    'audit_status' => $eventData['audit_status'] ?? null,
                    'audit_user_id' => $eventData['audit_user_id'] ?? null,
                ],
            ]);

            return true;

        } catch (\Throwable $e) {
            $this->log->error('Failed to store financial event in ClickHouse', [
                'event_type' => $eventData['event_type'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function getEventById(string $eventId): ?array
    {
        try {
            $result = $this->client->select(
                'SELECT * FROM event_store WHERE event_id = :event_id',
                ['event_id' => $eventId]
            );

            return $result->fetchOne() ?: null;

        } catch (\Throwable $e) {
            $this->log->error('Failed to retrieve event from ClickHouse', [
                'event_id' => $eventId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function getEventsByCorrelationId(string $correlationId): array
    {
        try {
            $result = $this->client->select(
                'SELECT * FROM event_store WHERE correlation_id = :correlation_id ORDER BY created_at',
                ['correlation_id' => $correlationId]
            );

            return $result->fetchAll();

        } catch (\Throwable $e) {
            $this->log->error('Failed to retrieve events by correlation ID', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function getMedicalEventsByPatientId(string $patientId, int $limit = 100): array
    {
        try {
            $result = $this->client->select(
                'SELECT * FROM event_store_medical WHERE patient_id = :patient_id ORDER BY created_at DESC LIMIT :limit',
                ['patient_id' => $patientId, 'limit' => $limit]
            );

            return $result->fetchAll();

        } catch (\Throwable $e) {
            $this->log->error('Failed to retrieve medical events', [
                'patient_id' => $patientId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function getFinancialEventsByTransactionId(string $transactionId): ?array
    {
        try {
            $result = $this->client->select(
                'SELECT * FROM event_store_financial WHERE transaction_id = :transaction_id',
                ['transaction_id' => $transactionId]
            );

            return $result->fetchOne() ?: null;

        } catch (\Throwable $e) {
            $this->log->error('Failed to retrieve financial events', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function getEventStats(array $filters = []): array
    {
        try {
            $where = [];
            $params = [];

            if (! empty($filters['vertical'])) {
                $where[] = 'vertical = :vertical';
                $params['vertical'] = $filters['vertical'];
            }

            if (! empty($filters['event_type'])) {
                $where[] = 'event_type = :event_type';
                $params['event_type'] = $filters['event_type'];
            }

            if (! empty($filters['from_date'])) {
                $where[] = 'created_at >= :from_date';
                $params['from_date'] = $filters['from_date'];
            }

            if (! empty($filters['to_date'])) {
                $where[] = 'created_at <= :to_date';
                $params['to_date'] = $filters['to_date'];
            }

            $whereClause = ! empty($where) ? 'WHERE '.implode(' AND ', $where) : '';

            $query = "SELECT 
                count() as total,
                countIf(is_medical) as medical,
                countIf(is_financial) as financial,
                countIf(is_emergency) as emergency,
                avg(processing_time_ms) as avg_processing_time
                FROM event_store {$whereClause}";

            $result = $this->client->select($query, $params);

            return $result->fetchOne() ?: [];

        } catch (\Throwable $e) {
            $this->log->error('Failed to get event stats', [
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
