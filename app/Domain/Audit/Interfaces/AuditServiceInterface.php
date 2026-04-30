<?php

declare(strict_types=1);

namespace App\Domain\Audit\Interfaces;

/**
 * Domain Interface - Audit Service Interface
 * 
 * Defines contract for audit logging operations.
 * Infrastructure layer implements this interface.
 * 
 * Architecture: Domain Layer - Interface
 * Follows Dependency Inversion Principle (DIP):
 * - Domain layer defines the contract
 * - Infrastructure layer provides implementation
 * - Application layer depends on abstraction, not concrete
 * 
 * @see \App\Services\AuditService (Infrastructure implementation)
 */
interface AuditServiceInterface
{
    /**
     * Record an audit event
     * 
     * @param  string  $action  Action performed
     * @param  string  $subjectType  Fully qualified class name of subject
     * @param  int|null  $subjectId  ID of subject entity
     * @param  array  $oldValues  Values before action
     * @param  array  $newValues  Values after action
     * @param  string  $correlationId  Distributed tracing ID
     */
    public function record(
        string $action,
        string $subjectType,
        ?int $subjectId,
        array $oldValues = [],
        array $newValues = [],
        string $correlationId = null
    ): void;

    /**
     * Log a model event (created, updated, deleted)
     * 
     * @param  string  $event  Event type (created, updated, deleted)
     * @param  object  $model  Model instance
     * @param  string  $correlationId  Distributed tracing ID
     */
    public function logModelEvent(
        string $event,
        object $model,
        string $correlationId = null
    ): void;

    /**
     * Log a payment-related event
     * 
     * @param  string  $action  Payment action (init, capture, refund, etc.)
     * @param  array  $paymentData  Payment details
     * @param  string  $correlationId  Distributed tracing ID
     */
    public function logPayment(
        string $action,
        array $paymentData,
        string $correlationId = null
    ): void;

    /**
     * Get audit logs for a specific user
     * 
     * @param  int  $userId  User ID
     * @param  array  $filters  Filter criteria (date_from, date_to, action, per_page)
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getLogsForUser(int $userId, array $filters = []);

    /**
     * Get audit logs for a specific subject
     * 
     * @param  string  $subjectType  Subject type
     * @param  int  $subjectId  Subject ID
     * @param  array  $filters  Filter criteria
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getLogsForSubject(
        string $subjectType,
        int $subjectId,
        array $filters = []
    );

    /**
     * Get audit logs by correlation ID
     * 
     * @param  string  $correlationId  Correlation ID
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getLogsByCorrelationId(string $correlationId);

    /**
     * Search audit logs with multiple criteria
     * 
     * @param  array  $criteria  Search criteria
     * @param  int  $perPage  Items per page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function searchLogs(array $criteria, int $perPage = 100);

    /**
     * Get audit statistics
     * 
     * @param  array  $filters  Filter criteria
     * @return array Statistics data
     */
    public function getStatistics(array $filters = []): array;

    /**
     * Anonymize logs older than specified date
     * 
     * @param  \Carbon\CarbonImmutable  $beforeDate  Cutoff date
     * @param  bool  $delete  True to delete, false to anonymize
     * @return int Number of affected logs
     */
    public function anonymizeLogsBefore(
        \Carbon\CarbonImmutable $beforeDate,
        bool $delete = false
    ): int;

    /**
     * Get retention statistics
     * 
     * @return array Retention data
     */
    public function getRetentionStatistics(): array;

    /**
     * Export audit logs to CSV
     * 
     * @param  array  $filters  Filter criteria
     * @param  int  $limit  Maximum records
     * @return string CSV content
     */
    public function exportToCsv(array $filters = [], int $limit = 10000): string;

    /**
     * Export audit logs to JSON
     * 
     * @param  array  $filters  Filter criteria
     * @param  int  $limit  Maximum records
     * @return string JSON content
     */
    public function exportToJson(array $filters = [], int $limit = 10000): string;
}
