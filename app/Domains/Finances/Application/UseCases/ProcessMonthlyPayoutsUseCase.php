<?php

declare(strict_types=1);

namespace App\Domains\Finances\Application\UseCases;

use App\Domains\Finances\Domain\Services\PayoutService;
use App\Services\AuditService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Use Case: ежемесячный расчёт и инициация выплат для тенанта.
 *
 * Оркестрирует поток:
 * 1. Определение периода (предыдущий месяц по умолчанию)
 * 2. Делегирование расчёта в PayoutService
 * 3. Аудит-лог
 *
 * Вызывается из CalculateAllTenantsEarningsJob или Filament action.
 *
 * @package App\Domains\Finances\Application\UseCases
 */
final readonly class ProcessMonthlyPayoutsUseCase
{
    public function __construct(
        private PayoutService $payoutService,
        private AuditService $audit,
        private LoggerInterface $logger,
    ) {}

    /**
     * Запустить расчёт и инициацию выплат для тенанта.
     *
     * @param int                  $tenantId   ID тенанта
     * @param CarbonImmutable|null $periodEnd  Конец периода (по умолчанию — конец предыдущего месяца)
     * @param string|null          $correlationId Трейсинг-идентификатор
     */
    public function execute(
        int $tenantId,
        ?CarbonImmutable $periodEnd = null,
        ?string $correlationId = null,
    ): void {
        $correlationId = $correlationId ?? Str::uuid()->toString();
        $periodEnd     = $periodEnd ?? CarbonImmutable::createFromTimestamp(Carbon::now()->getTimestamp())->subMonth()->endOfMonth();

        $this->logger->info('ProcessMonthlyPayoutsUseCase started', [
            'tenant_id'      => $tenantId,
            'period_end'     => $periodEnd->toDateString(),
            'correlation_id' => $correlationId,
        ]);

        $this->payoutService->calculateAndInitiatePayoutsForTenant(
            tenantId: $tenantId,
            periodEnd: $periodEnd,
        );

        $this->audit->record(
            action: 'monthly_payout_processed',
            subjectType: 'tenant',
            subjectId: $tenantId,
            newValues: [
                'period_end' => $periodEnd->toDateString(),
            ],
            correlationId: $correlationId,
        );

        $this->logger->info('ProcessMonthlyPayoutsUseCase completed', [
            'tenant_id'      => $tenantId,
            'period_end'     => $periodEnd->toDateString(),
            'correlation_id' => $correlationId,
        ]);
    }
}
