<?php declare(strict_types=1);

namespace App\Domains\Finances\Domain\Services;

use App\Domains\Finances\Domain\Enums\PayoutStatus;
use App\Domains\Finances\Domain\Events\PayoutInitiated;
use App\Domains\Finances\Domain\Interfaces\EarningCalculatorInterface;
use App\Domains\Finances\Domain\Interfaces\PayoutRepositoryInterface;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Services\WalletService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Доменный сервис: расчёт и инициация выплат тенантам.
 *
 * Агрегирует доходы по всем вертикалям через EarningCalculatorInterface[],
 * списывает через WalletService и диспатчит PayoutInitiated event.
 *
 * Канон: fraud-check → DB::transaction → audit → event.
 *
 * @package App\Domains\Finances\Domain\Services
 */
final readonly class PayoutService
{
    /**
     * @param PayoutRepositoryInterface     $payoutRepository Репозиторий выплат
     * @param WalletService                 $walletService    Сервис кошельков
     * @param FraudControlService           $fraud            Фрод-контроль
     * @param AuditService                  $audit            Аудит-сервис
     * @param iterable<EarningCalculatorInterface> $calculators Калькуляторы по вертикалям
     * @param DatabaseManager               $db               Менеджер БД (транзакции)
     * @param EventDispatcher               $events           Диспатчер событий
     * @param LoggerInterface               $logger           Логгер
     */
    public function __construct(
        private PayoutRepositoryInterface $payoutRepository,
        private WalletService $walletService,
        private FraudControlService $fraud,
        private AuditService $audit,
        private iterable $calculators,
        private DatabaseManager $db,
        private EventDispatcher $events,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Рассчитать и инициировать выплату для одного тенанта за период.
     *
     * @param int              $tenantId  Идентификатор тенанта
     * @param CarbonImmutable  $periodEnd Конец расчётного периода
     *
     * @throws \App\Exceptions\FraudBlockedException
     * @throws \App\Exceptions\InsufficientBalanceException
     */
    public function calculateAndInitiatePayoutsForTenant(int $tenantId, CarbonImmutable $periodEnd): void
    {
        $correlationId = Str::uuid()->toString();
        $periodStart = $periodEnd->subMonth()->startOfMonth();

        $this->logger->info('Payout calculation started', [
            'tenant_id'      => $tenantId,
            'period_start'   => $periodStart->toDateString(),
            'period_end'     => $periodEnd->toDateString(),
            'correlation_id' => $correlationId,
        ]);

        $totalEarnings = $this->aggregateEarningsFromAllVerticals($tenantId, $periodStart, $periodEnd, $correlationId);

        if ($totalEarnings <= 0) {
            $this->logger->info('No earnings for payout', [
                'tenant_id'      => $tenantId,
                'correlation_id' => $correlationId,
            ]);
            return;
        }

        $this->fraud->check(
            userId: $tenantId,
            operationType: 'payout_initiation',
            amount: $totalEarnings,
            correlationId: $correlationId,
        );

        $this->db->transaction(function () use ($tenantId, $totalEarnings, $periodStart, $periodEnd, $correlationId): void {
            $this->walletService->debit(
                walletId: $tenantId,
                amount: $totalEarnings,
                reason: 'payout',
                correlationId: $correlationId,
            );

            $this->events->dispatch(new PayoutInitiated(
                tenantId: $tenantId,
                businessGroupId: null,
                amount: $totalEarnings,
                periodStart: $periodStart->toIso8601String(),
                periodEnd: $periodEnd->toIso8601String(),
                correlationId: $correlationId,
            ));

            $this->audit->record(
                'payout_initiated',
                'payout',
                $tenantId,
                [],
                [
                    'amount'       => $totalEarnings,
                    'period_start' => $periodStart->toIso8601String(),
                    'period_end'   => $periodEnd->toIso8601String(),
                ],
                $correlationId
            );

            $this->logger->info('Payout initiated successfully', [
                'tenant_id'      => $tenantId,
                'total_earnings' => $totalEarnings,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    /**
     * Агрегирует доходы по всем зарегистрированным вертикалям.
     */
    private function aggregateEarningsFromAllVerticals(
        int $tenantId,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
        string $correlationId,
    ): int {
        $totalEarnings = 0;

        foreach ($this->calculators as $calculator) {
            $vertical = $calculator->getVertical();
            $earnings = $calculator->calculateForTenant($tenantId, $periodStart, $periodEnd);
            $totalEarnings += $earnings;

            $this->logger->info('Vertical earnings calculated', [
                'tenant_id'      => $tenantId,
                'vertical'       => $vertical,
                'earnings'       => $earnings,
                'correlation_id' => $correlationId,
            ]);
        }

        return $totalEarnings;
    }
}
