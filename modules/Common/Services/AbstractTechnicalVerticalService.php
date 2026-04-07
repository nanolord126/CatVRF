<?php

declare(strict_types=1);

namespace Modules\Common\Services;

use App\Models\Tenant;
use App\Services\FraudControlService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Modules\Common\Contracts\TechnicalVerticalServiceInterface;

/**
 * Абстрактный базовый сервис для всех технических вертикалей.
 *
 * Предоставляет:
 * - Иммутабельный tenant binding (forTenant() → clone)
 * - Ленивый correlation_id (UUID v4 при первом обращении)
 * - getTenantScope() для безопасной фильтрации Eloquent-запросов
 * - resolveTenantId() с защитой от случайного обращения без tenant-привязки
 * - auditLog() / auditError() — единый стиль аудит-логирования
 * - fraudCheck() — обязательный вызов FraudControlService перед мутациями
 *
 * КАНОН 2026: не добавлять static facades, не использовать DB::, Log:: напрямую.
 * Все зависимости получать через конструктор в дочернем классе.
 */
abstract class AbstractTechnicalVerticalService implements TechnicalVerticalServiceInterface
{
    protected Tenant $tenant;

    protected string $correlationId;

    // ──────────────────────────────────────────────────────────────────
    //  TechnicalVerticalServiceInterface implementation
    // ──────────────────────────────────────────────────────────────────

    /**
     * Иммутабельный builder: привязывает тенант, возвращает клон.
     */
    public function forTenant(Tenant $tenant): static
    {
        $clone = clone $this;
        $clone->tenant = $tenant;

        return $clone;
    }

    /**
     * Возвращает callable-скоп для Eloquent Builder.
     *
     * @throws \LogicException Если tenant не привязан.
     */
    public function getTenantScope(): callable
    {
        $tenantId = $this->resolveTenantId();

        return static fn (Builder $query) => $query->where('tenant_id', $tenantId);
    }

    /**
     * Ленивая инициализация correlation_id.
     */
    public function getCorrelationId(): string
    {
        if (!isset($this->correlationId)) {
            $this->correlationId = Str::uuid()->toString();
        }

        return $this->correlationId;
    }

    /**
     * Иммутабельный builder: создаёт клон сервиса с указанным correlation_id.
     */
    public function withCorrelationId(string $correlationId): static
    {
        $clone = clone $this;
        $clone->correlationId = $correlationId;

        return $clone;
    }

    /**
     * Дочерний класс обязан реализовать логику проверки feature flag.
     */
    abstract public function isEnabled(): bool;

    // ──────────────────────────────────────────────────────────────────
    //  Protected helpers для дочерних сервисов
    // ──────────────────────────────────────────────────────────────────

    /**
     * Безопасное чтение tenant_id с ранним падением, если forTenant() не вызвали.
     *
     * @throws \LogicException
     */
    protected function resolveTenantId(): int
    {
        if (!isset($this->tenant)) {
            throw new \LogicException(
                static::class . ': tenant is not set. Call forTenant($tenant) before using this service.'
            );
        }

        return (int) $this->tenant->getKey();
    }

    /**
     * Единый формат аудит-лога (info).
     * Всегда включает correlation_id и tenant_id.
     */
    protected function auditLog(LogManager $log, string $event, array $context = []): void
    {
        $log->channel('audit')->info($event, array_merge([
            'correlation_id' => $this->getCorrelationId(),
            'tenant_id'      => $this->tenant->getKey() ?? null,
            'service'        => static::class,
        ], $context));
    }

    /**
     * Единый формат аудит-лога (error + stack trace).
     */
    protected function auditError(LogManager $log, string $event, \Throwable $e, array $context = []): void
    {
        $log->channel('audit')->error($event, array_merge([
            'correlation_id' => $this->getCorrelationId(),
            'tenant_id'      => $this->tenant->getKey() ?? null,
            'service'        => static::class,
            'error'          => $e->getMessage(),
            'trace'          => $e->getTraceAsString(),
        ], $context));
    }

    /**
     * Единая точка вызова FraudControlService.
     * Бросает исключение при обнаружении фрода.
     *
     * @throws \RuntimeException При score выше порога
     */
    protected function fraudCheck(FraudControlService $fraud, array $payload): void
    {
        $payload['correlation_id'] = $this->getCorrelationId();
        $payload['tenant_id']      = $this->tenant->getKey() ?? null;

        $fraud->check($payload);
    }
}
