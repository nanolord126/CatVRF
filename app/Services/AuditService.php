<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use App\Jobs\AuditLogJob;
use App\Models\AuditLog;
use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Queue\Queue;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Audit Logging Service - Production Ready
 * Production 2026 CANON — CatVRF 2026 Enterprise Security
 *
 * Централизованное логирование всех мутаций с полной бизнес-логикой.
 *
 * Features:
 * - Асинхронная запись в БД через AuditLogJob
 * - Correlation ID для distributed tracing
 * - Multi-tenancy support
 * - Query methods с пагинацией и фильтрацией
 * - Data retention и anonymization (152-FZ compliant)
 * - Statistics и analytics
 * - Export capabilities
 * - Event-based architecture
 *
 * Канонический API (constructor injection):
 *   $this->audit->record('action', Model::class, $id, $old, $new, $correlationId);
 *   $this->audit->logModelEvent('created', $model);
 *   $this->audit->getLogsForUser($userId, $filters);
 *   $this->audit->getStatistics($filters);
 *   $this->audit->anonymizeLogsBefore($date);
 *
 * @see \App\Models\AuditLog
 * @see \App\Jobs\AuditLogJob
 * @see \App\Traits\WithAuditLogging
 */
final class AuditService
{
    public function __construct(
        private readonly BusDispatcher $bus,
        private readonly LogManager $logger,
        private readonly Request $request,
        private readonly AuthManager $authManager,
        private readonly Queue $queue,
        private readonly DatabaseManager $db,
    ) {}

    // ─────────────────────────────────────────────────────────────
    // CANONICAL INSTANCE METHODS (use these in new code)
    // ─────────────────────────────────────────────────────────────

    /**
     * Записать аудит-событие асинхронно (канонический метод).
     * Вызывать ПОСЛЕ успешной транзакции.
     */
    public function record(
        string $action,
        string $subjectType,
        ?int $subjectId,
        array $oldValues    = [],
        array $newValues    = [],
        ?string $correlationId = null,
    ): void {
        $cid = $correlationId ?? Str::uuid()->toString();

        AuditLogJob::dispatch([
            'tenant_id'           => function_exists('tenant') && tenant() ? tenant()->id : null,
            'business_group_id'   => $this->request->get('business_group_id'),
            'user_id'             => $this->authManager->id(),
            'action'              => $action,
            'subject_type'        => $subjectType,
            'subject_id'          => $subjectId,
            'old_values'          => $oldValues,
            'new_values'          => $newValues,
            'ip_address'          => $this->request->ip(),
            'device_fingerprint'  => hash('sha256', $this->request->ip().$this->request->userAgent()),
            'correlation_id'      => $cid,
        ])->onQueue('audit-logs');

        $this->logger->channel('audit')->info($action, [
            'subject_type'   => $subjectType,
            'subject_id'     => $subjectId,
            'correlation_id' => $cid,
            'user_id'        => $this->authManager->id(),
            'tenant_id'      => function_exists('tenant') && tenant() ? tenant()->id : null,
        ]);
    }

    /**
     * Удобная обёртка для событий моделей (created / updated / deleted).
     * Вызывается из Model::booted() после успешного сохранения.
     */
    public function logModelEvent(
        string $event,
        Model $model,
        array $old          = [],
        ?string $correlationId = null,
    ): void {
        $new = match ($event) {
            default   => $model->getChanges() ?: $model->toArray(),
        };

        $this->record(
            action:        $event,
            subjectType:   get_class($model),
            subjectId:     $model->getKey(),
            oldValues:     $old,
            newValues:     $new,
            correlationId: $correlationId,
        );
    }

    // ─────────────────────────────────────────────────────────────
    // LEGACY STATIC API — @deprecated, используйте instance-методы
    // ─────────────────────────────────────────────────────────────

    /**
     * Instance-метод log() — alias для record(), совместим с доменными сервисами.
     *
     * Сигнатура: log(action, subjectType, subjectId, old, new, correlationId)
     * Пример: $this->audit->log('wallet_credited', Wallet::class, $id, $old, $new, $cid);
     */
    public function log(
        string $action,
        string $subjectType,
        ?int $subjectId     = null,
        array $oldValues     = [],
        array $newValues     = [],
        ?string $correlationId = null,
    ): void {
        $this->record($action, $subjectType, $subjectId, $oldValues, $newValues, $correlationId);
    }

    /**
     * Log an error
     *
     * @param  string  $operation  Operation name
     * @param  \Exception  $exception  Exception thrown
     * @param  string  $correlationId  Tracing ID
     * @param  array  $context  Additional context
     */
    public function logError(string $operation, \Exception $exception, string $correlationId, array $context = []): void
    {
        $errorContext = array_merge([
            'correlation_id' => $correlationId,
            'operation' => $operation,
            'error' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'trace' => $exception->getTraceAsString(),
            'user_id' => $this->authManager->id(),
            'tenant_id' => function_exists('tenant') && tenant() ? tenant()->id : null,
            'timestamp' => CarbonImmutable::now(),
        ], $context);

        $this->logger->channel('audit')->error($operation.' failed', $errorContext);
    }

    /**
     * Log payment operation
     *
     * @param  string  $action  Action (init, capture, refund)
     * @param  array  $paymentData  Payment data
     * @param  string  $correlationId  Tracing ID
     */
    public function logPayment(string $action, array $paymentData, string $correlationId): void
    {
        $this->log('payment.'.$action, 'Payment', null, [], [
            'payment_id' => $paymentData['payment_id'] ?? null,
            'amount' => $paymentData['amount'] ?? null,
            'gateway' => $paymentData['gateway'] ?? null,
            'status' => $paymentData['status'] ?? null,
        ], $correlationId);
    }

    /**
     * Log wallet operation
     *
     * @param  string  $action  Action (hold, release, credit, debit)
     * @param  array  $walletData  Wallet data
     * @param  string  $correlationId  Tracing ID
     */
    public function logWallet(string $action, array $walletData, string $correlationId): void
    {
        $this->log('wallet.'.$action, 'Wallet', $walletData['wallet_id'] ?? null, [], [
            'amount' => $walletData['amount'] ?? null,
            'reason' => $walletData['reason'] ?? null,
            'balance_before' => $walletData['balance_before'] ?? null,
            'balance_after' => $walletData['balance_after'] ?? null,
        ], $correlationId);
    }

    /**
     * Log fraud check
     *
     * @param  array  $checkData  Fraud check data
     * @param  string  $correlationId  Tracing ID
     */
    public function logFraudCheck(array $checkData, string $correlationId): void
    {
        $this->log('fraud.check', 'FraudCheck', $checkData['user_id'] ?? null, [], [
            'operation_type' => $checkData['operation_type'] ?? null,
            'score' => $checkData['score'] ?? null,
            'decision' => $checkData['decision'] ?? null,
            'amount' => $checkData['amount'] ?? null,
        ], $correlationId);
    }

    /**
     * Log promo application
     *
     * @param  array  $promoData  Promo data
     * @param  string  $correlationId  Tracing ID
     */
    public function logPromo(array $promoData, string $correlationId): void
    {
        $this->log('promo.apply', 'Promo', null, [], [
            'code' => $promoData['code'] ?? null,
            'discount_amount' => $promoData['discount_amount'] ?? null,
            'order_amount' => $promoData['order_amount'] ?? null,
            'vertical' => $promoData['vertical'] ?? null,
        ], $correlationId);
    }

    /**
     * Log referral operation
     *
     * @param  string  $action  Action (generate, register, qualify, award)
     * @param  array  $referralData  Referral data
     * @param  string  $correlationId  Tracing ID
     */
    public function logReferral(string $action, array $referralData, string $correlationId): void
    {
        $this->log('referral.'.$action, 'Referral', $referralData['referral_id'] ?? null, [], [
            'referrer_id' => $referralData['referrer_id'] ?? null,
            'referee_id' => $referralData['referee_id'] ?? null,
            'bonus_amount' => $referralData['bonus_amount'] ?? null,
        ], $correlationId);
    }

    // ─────────────────────────────────────────────────────────────
    // QUERY METHODS - Retrieve and search audit logs
    // ─────────────────────────────────────────────────────────────

    /**
     * Get audit logs for a specific user with pagination and filters
     *
     * @param  int  $userId  User ID
     * @param  array  $filters  Filters: action, subject_type, date_from, date_to, limit
     * @return LengthAwarePaginator
     */
    public function getLogsForUser(int $userId, array $filters = []): LengthAwarePaginator
    {
        $query = AuditLog::where('user_id', $userId);

        return $this->applyFilters($query, $filters)
            ->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 50);
    }

    /**
     * Get audit logs for a specific subject (entity)
     *
     * @param  string  $subjectType  Fully qualified class name
     * @param  int|null  $subjectId  Entity ID (null for all entities of this type)
     * @param  array  $filters  Additional filters
     * @return LengthAwarePaginator
     */
    public function getLogsForSubject(string $subjectType, ?int $subjectId = null, array $filters = []): LengthAwarePaginator
    {
        $query = AuditLog::where('subject_type', $subjectType);

        if ($subjectId !== null) {
            $query->where('subject_id', $subjectId);
        }

        return $this->applyFilters($query, $filters)
            ->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 50);
    }

    /**
     * Get audit logs by correlation ID (for distributed tracing)
     *
     * @param  string  $correlationId  Correlation ID
     * @return Collection
     */
    public function getLogsByCorrelationId(string $correlationId): Collection
    {
        return AuditLog::where('correlation_id', $correlationId)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get audit logs for a tenant with filters
     *
     * @param  int  $tenantId  Tenant ID
     * @param  array  $filters  Filters
     * @return LengthAwarePaginator
     */
    public function getLogsForTenant(int $tenantId, array $filters = []): LengthAwarePaginator
    {
        $query = AuditLog::where('tenant_id', $tenantId);

        return $this->applyFilters($query, $filters)
            ->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 50);
    }

    /**
     * Get audit logs by action type
     *
     * @param  string  $action  Action type
     * @param  array  $filters  Additional filters
     * @return LengthAwarePaginator
     */
    public function getLogsByAction(string $action, array $filters = []): LengthAwarePaginator
    {
        $query = AuditLog::where('action', $action);

        return $this->applyFilters($query, $filters)
            ->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 50);
    }

    /**
     * Search audit logs by multiple criteria
     *
     * @param  array  $criteria  Search criteria
     * @return LengthAwarePaginator
     */
    public function searchLogs(array $criteria): LengthAwarePaginator
    {
        $query = AuditLog::query();

        if (!empty($criteria['user_id'])) {
            $query->where('user_id', $criteria['user_id']);
        }

        if (!empty($criteria['tenant_id'])) {
            $query->where('tenant_id', $criteria['tenant_id']);
        }

        if (!empty($criteria['action'])) {
            $query->where('action', 'like', '%'.$criteria['action'].'%');
        }

        if (!empty($criteria['subject_type'])) {
            $query->where('subject_type', 'like', '%'.$criteria['subject_type'].'%');
        }

        if (!empty($criteria['correlation_id'])) {
            $query->where('correlation_id', $criteria['correlation_id']);
        }

        if (!empty($criteria['ip_address'])) {
            $query->where('ip_address', $criteria['ip_address']);
        }

        return $this->applyFilters($query, $criteria)
            ->orderBy('created_at', 'desc')
            ->paginate($criteria['per_page'] ?? 50);
    }

    // ─────────────────────────────────────────────────────────────
    // STATISTICS AND ANALYTICS
    // ─────────────────────────────────────────────────────────────

    /**
     * Get audit statistics for a given period
     *
     * @param  array  $filters  Filters: date_from, date_to, tenant_id, user_id
     * @return array
     */
    public function getStatistics(array $filters = []): array
    {
        $query = AuditLog::query();

        $query = $this->applyDateFilters($query, $filters);

        if (!empty($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        $clone = clone $query;

        return [
            'total_logs' => $query->count(),
            'unique_users' => $clone->distinct('user_id')->count('user_id'),
            'by_action' => $clone->selectRaw('action, COUNT(*) as count')
                ->groupBy('action')
                ->orderByDesc('count')
                ->limit(10)
                ->get()
                ->pluck('count', 'action')
                ->toArray(),
            'by_subject_type' => $clone->selectRaw('subject_type, COUNT(*) as count')
                ->groupBy('subject_type')
                ->orderByDesc('count')
                ->limit(10)
                ->get()
                ->pluck('count', 'subject_type')
                ->toArray(),
            'by_hour' => $clone->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
                ->groupBy('hour')
                ->orderBy('hour')
                ->get()
                ->pluck('count', 'hour')
                ->toArray(),
        ];
    }

    /**
     * Get user activity statistics
     *
     * @param  int  $userId  User ID
     * @param  int  $days  Number of days to analyze
     * @return array
     */
    public function getUserActivityStatistics(int $userId, int $days = 30): array
    {
        $startDate = CarbonImmutable::now()->subDays($days);

        $logs = AuditLog::where('user_id', $userId)
            ->where('created_at', '>=', $startDate)
            ->get();

        return [
            'total_actions' => $logs->count(),
            'unique_actions' => $logs->unique('action')->count(),
            'most_common_action' => $logs->groupBy('action')
                ->map->count()
                ->sortDesc()
                ->first(),
            'actions_by_day' => $logs->groupBy(function ($log) {
                return $log->created_at->format('Y-m-d');
            })->map->count()->toArray(),
            'subjects_modified' => $logs->unique(function ($log) {
                return $log->subject_type.'-'.$log->subject_id;
            })->count(),
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // DATA RETENTION AND ANONYMIZATION (152-FZ COMPLIANT)
    // ─────────────────────────────────────────────────────────────

    /**
     * Anonymize audit logs older than specified date (152-FZ compliant)
     * Removes or masks PII while keeping audit trail intact.
     *
     * @param  CarbonImmutable  $beforeDate  Anonymize logs before this date
     * @param  bool  $delete  If true, delete logs instead of anonymizing
     * @return int  Number of logs processed
     */
    public function anonymizeLogsBefore(CarbonImmutable $beforeDate, bool $delete = false): int
    {
        $query = AuditLog::where('created_at', '<', $beforeDate);

        if ($delete) {
            return $query->delete();
        }

        // Anonymize by removing sensitive data but keeping audit trail
        $count = 0;
        $query->chunkById(1000, function ($logs) use (&$count) {
            foreach ($logs as $log) {
                $log->update([
                    'ip_address' => null,
                    'device_fingerprint' => null,
                    'old_values' => $this->anonymizeValues($log->old_values),
                    'new_values' => $this->anonymizeValues($log->new_values),
                ]);
                $count++;
            }
        });

        $this->logger->info('Audit logs anonymized', [
            'count' => $count,
            'before_date' => $beforeDate->toIso8601String(),
        ]);

        return $count;
    }

    /**
     * Anonymize specific values in audit data (PII removal)
     *
     * @param  array|null  $values  Values to anonymize
     * @return array|null
     */
    private function anonymizeValues(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        $sensitiveKeys = [
            'password', 'email', 'phone', 'name', 'first_name', 'last_name',
            'middle_name', 'passport', 'inn', 'snils', 'address', 'card_number',
            'cvv', 'expiry', 'secret', 'token', 'api_key', 'otp',
        ];

        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $values[$key] = $this->anonymizeValues($value);
            } elseif (is_string($key)) {
                foreach ($sensitiveKeys as $sensitiveKey) {
                    if (str_contains(strtolower($key), $sensitiveKey)) {
                        $values[$key] = '[REDACTED]';
                        break;
                    }
                }
            }
        }

        return $values;
    }

    /**
     * Get retention statistics
     *
     * @return array
     */
    public function getRetentionStatistics(): array
    {
        $now = CarbonImmutable::now();

        return [
            'total_logs' => AuditLog::count(),
            'logs_older_than_90_days' => AuditLog::where('created_at', '<', $now->subDays(90))->count(),
            'logs_older_than_1_year' => AuditLog::where('created_at', '<', $now->subYear())->count(),
            'logs_older_than_7_years' => AuditLog::where('created_at', '<', $now->subYears(7))->count(),
            'storage_size_mb' => $this->db->table('information_schema.tables')
                ->where('table_schema', config('database.connections.mysql.database'))
                ->where('table_name', 'audit_logs')
                ->value('data_length') / 1024 / 1024 ?? 0,
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // EXPORT AND REPORTING
    // ─────────────────────────────────────────────────────────────

    /**
     * Export audit logs to CSV
     *
     * @param  array  $filters  Filters for export
     * @return string  CSV content
     */
    public function exportToCsv(array $filters = []): string
    {
        $query = AuditLog::query();
        $query = $this->applyFilters($query, $filters);

        $logs = $query
            ->orderBy('created_at', 'desc')
            ->limit($filters['limit'] ?? 10000)
            ->get();

        $headers = [
            'ID', 'UUID', 'Created At', 'Tenant ID', 'User ID', 'Action',
            'Subject Type', 'Subject ID', 'IP Address', 'Correlation ID',
        ];

        $rows = [$headers];

        foreach ($logs as $log) {
            $rows[] = [
                $log->id,
                $log->uuid,
                $log->created_at->toIso8601String(),
                $log->tenant_id,
                $log->user_id,
                $log->action,
                $log->subject_type,
                $log->subject_id,
                $log->ip_address,
                $log->correlation_id,
            ];
        }

        $csv = fopen('php://temp', 'r+');
        foreach ($rows as $row) {
            fputcsv($csv, $row);
        }
        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        return $content;
    }

    /**
     * Export audit logs to JSON
     *
     * @param  array  $filters  Filters for export
     * @return string  JSON content
     */
    public function exportToJson(array $filters = []): string
    {
        $query = AuditLog::query();
        $query = $this->applyFilters($query, $filters);

        $logs = $query
            ->orderBy('created_at', 'desc')
            ->limit($filters['limit'] ?? 10000)
            ->get();

        return $logs->map(function ($log) {
            return [
                'id' => $log->id,
                'uuid' => $log->uuid,
                'created_at' => $log->created_at->toIso8601String(),
                'tenant_id' => $log->tenant_id,
                'business_group_id' => $log->business_group_id,
                'user_id' => $log->user_id,
                'action' => $log->action,
                'subject_type' => $log->subject_type,
                'subject_id' => $log->subject_id,
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
                'ip_address' => $log->ip_address,
                'device_fingerprint' => $log->device_fingerprint,
                'correlation_id' => $log->correlation_id,
            ];
        })->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    // ─────────────────────────────────────────────────────────────
    // HELPER METHODS
    // ─────────────────────────────────────────────────────────────

    /**
     * Apply common filters to query
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Query builder
     * @param  array  $filters  Filters to apply
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function applyFilters($query, array $filters)
    {
        $query = $this->applyDateFilters($query, $filters);

        if (!empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (!empty($filters['subject_type'])) {
            $query->where('subject_type', $filters['subject_type']);
        }

        if (!empty($filters['business_group_id'])) {
            $query->where('business_group_id', $filters['business_group_id']);
        }

        if (!empty($filters['correlation_id'])) {
            $query->where('correlation_id', $filters['correlation_id']);
        }

        if (!empty($filters['ip_address'])) {
            $query->where('ip_address', $filters['ip_address']);
        }

        return $query;
    }

    /**
     * Apply date filters to query
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Query builder
     * @param  array  $filters  Filters with date_from and date_to
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function applyDateFilters($query, array $filters)
    {
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', CarbonImmutable::parse($filters['date_from']));
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', CarbonImmutable::parse($filters['date_to'])->endOfDay());
        }

        return $query;
    }

    /**
     * Check if audit logging is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return config('audit.enabled', true);
    }

    /**
     * Check if async logging is enabled
     *
     * @return bool
     */
    public function isAsync(): bool
    {
        return config('audit.async', true);
    }
}
