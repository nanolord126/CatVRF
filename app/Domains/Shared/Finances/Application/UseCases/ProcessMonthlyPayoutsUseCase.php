<?php

declare(strict_types=1);

namespace App\Domains\Finances\Application\UseCases;

use App\Services\Fraud\FraudControlService;

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
 */
final readonly class ProcessMonthlyPayoutsUseCase
{
    public function __construct(private readonly FraudControlService $fraudControlService,
        private readonly PayoutService $payoutService,
        private readonly AuditService $audit,
        private readonly LoggerInterface $logger,) {}

    /**
     * Запустить расчёт и инициацию выплат для тенанта.
     *
     * @param  int  $tenantId  ID тенанта
     * @param  CarbonImmutable|null  $periodEnd  Конец периода (по умолчанию — конец предыдущего месяца)
     * @param  string|null  $correlationId  Трейсинг-идентификатор
     */
    public function execute(
        int $tenantId,
        ?CarbonImmutable $periodEnd = null,
        ?string $correlationId = null,
    ): void {
        $this->fraudControlService->check('execute', ['context' => __CLASS__]);
        $correlationId = $correlationId ?? Str::uuid()->toString();
        $periodEnd     = $periodEnd ?? CarbonImmutable::createFromTimestamp(CarbonImmutable::now()->getTimestamp())->subMonth()->endOfMonth();

        $this->logger->$this->logger->info('ProcessMonthlyPayoutsUseCase started', [
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

        $this->logger->$this->logger->info('ProcessMonthlyPayoutsUseCase completed', [
            'tenant_id'      => $tenantId,
            'period_end'     => $periodEnd->toDateString(),
            'correlation_id' => $correlationId,
        ]);
    }
}
