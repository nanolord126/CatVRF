<?php

declare(strict_types=1);

namespace App\Domains\Audit\Facades;

use Illuminate\Support\Facades\Facade;
use App\Domains\Audit\DTOs\CreateAuditLogDto;
use Carbon\Carbon;

/**
 * Audit — Facade for AuditService.
 * Provides static access to audit logging functionality.
 *
 * @method static void record(string $action, string $subjectType, ?int $subjectId, array $oldValues = [], array $newValues = [], ?string $correlationId = null)
 * @method static void recordFromDto(CreateAuditLogDto $dto)
 * @method static \Illuminate\Database\Eloquent\Collection getLogsForSubject(string $subjectType, ?int $subjectId = null, int $limit = 100)
 * @method static \Illuminate\Database\Eloquent\Collection getLogsByCorrelationId(string $correlationId)
 * @method static \Illuminate\Database\Eloquent\Collection getLogsForUser(int $userId, int $limit = 100)
 * @method static \Illuminate\Database\Eloquent\Collection getLogsForTenant(int $tenantId, int $limit = 100)
 * @method static \Illuminate\Database\Eloquent\Collection searchByPayload(string $searchTerm, int $limit = 100)
 * @method static \Illuminate\Database\Eloquent\Collection getLogsInDateRange(Carbon $from, Carbon $to, int $limit = 100)
 * @method static int deleteLogsForUser(int $userId)
 * @method static int deleteLogsForSubject(string $subjectType, ?int $subjectId = null)
 * @method static int deleteLogsByCorrelationId(string $correlationId)
 * @method static int pruneOldLogs()
 * @method static bool isEnabled()
 * @method static bool isAsync()
 * @method static int getRetentionMonths()
 * @method static void logPayment(string $action, array $paymentData, ?string $correlationId = null)
 * @method static void logWallet(string $action, array $walletData, ?string $correlationId = null)
 * @method static void logFraudCheck(array $checkData, ?string $correlationId = null)
 * @method static void logPromo(array $promoData, ?string $correlationId = null)
 * @method static void logError(string $operation, \Exception $exception, ?string $correlationId = null, array $context = [])
 * @method static void logAuth(string $action, array $authData, ?string $correlationId = null)
 *
 * @see \App\Domains\Audit\Services\AuditService
 */
final class Audit extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Domains\Audit\Services\AuditService::class;
    }
}
