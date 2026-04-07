<?php declare(strict_types=1);

namespace App\Domains\Freelance\Listeners;

use App\Domains\Freelance\Events\PaymentMilestoneReleased;
use App\Services\AuditService;
use App\Services\WalletService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * ReleaseFreelancerPaymentListener — слушатель события релиза оплаты за milestone.
 *
 * Начисляет фрилансеру оплату за завершённый milestone
 * (минус комиссия платформы). Работает асинхронно.
 *
 * @package App\Domains\Freelance\Listeners
 */
final readonly class ReleaseFreelancerPaymentListener implements ShouldQueue
{
    /**
     * Ставка комиссии платформы.
     */
    private const COMMISSION_RATE = 0.14;

    public function __construct(
        private AuditService $audit,
        private WalletService $wallet,
        private LoggerInterface $logger,
    ) {}

    /**
     * Обработать событие релиза milestone.
     *
     * Начисляет сумму milestone (минус комиссия) в wallet фрилансера,
     * логирует через аудит и в основной канал.
     */
    public function handle(PaymentMilestoneReleased $event): void
    {
        $contract = $event->contract;
        $amount = $event->amount;
        $milestoneNumber = $event->milestoneNumber;
        $correlationId = $event->correlationId;

        $commission = (int) ($amount * self::COMMISSION_RATE);
        $payout = (int) ($amount - $commission);

        $this->wallet->credit(
            walletId: $contract->freelancer_id,
            amount: $payout,
            type: 'freelance_milestone_payout',
            correlationId: $correlationId,
            metadata: [
                'contract_id' => $contract->id,
                'milestone_number' => $milestoneNumber,
                'gross_amount' => $amount,
                'commission' => $commission,
            ],
        );

        $this->audit->log(
            action: 'freelance_milestone_payment_released',
            subjectType: $contract::class,
            subjectId: $contract->id,
            old: [],
            new: [
                'milestone_number' => $milestoneNumber,
                'payout' => $payout,
                'commission' => $commission,
            ],
            correlationId: $correlationId,
        );

        $this->logger->info('Freelancer milestone payment released', [
            'contract_id' => $contract->id,
            'freelancer_id' => $contract->freelancer_id,
            'milestone' => $milestoneNumber,
            'payout' => $payout,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Обработка сбоя — логирование ошибки.
     */
    public function failed(PaymentMilestoneReleased $event, \Throwable $exception): void
    {
        $this->logger->error('ReleaseFreelancerPaymentListener failed', [
            'event' => PaymentMilestoneReleased::class,
            'error' => $exception->getMessage(),
            'correlation_id' => $event->correlationId,
        ]);
    }
}
