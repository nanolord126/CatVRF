<?php declare(strict_types=1);

namespace App\Domains\Freelance\Listeners;

use App\Domains\Freelance\Events\ProposalAccepted;
use App\Services\AuditService;
use App\Services\WalletService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * DeductProposalCommissionListener — слушатель события принятия предложения.
 *
 * Списывает комиссию платформы с фрилансера
 * при принятии его предложения клиентом.
 * Работает асинхронно через очередь.
 *
 * @package App\Domains\Freelance\Listeners
 */
final readonly class DeductProposalCommissionListener implements ShouldQueue
{
    /**
     * Ставка комиссии платформы (14%).
     */
    private const COMMISSION_RATE = 0.14;

    public function __construct(
        private AuditService $audit,
        private WalletService $wallet,
        private LoggerInterface $logger,
    ) {}

    /**
     * Обработать событие принятия предложения.
     *
     * Рассчитывает и списывает комиссию платформы
     * с кошелька фрилансера, логирует в аудит.
     */
    public function handle(ProposalAccepted $event): void
    {
        $proposal = $event->proposal;
        $correlationId = $event->correlationId;
        $commissionAmount = (int) ($proposal->price * self::COMMISSION_RATE);

        $this->wallet->credit(
            walletId: $proposal->freelancer_id,
            amount: -$commissionAmount,
            type: 'freelance_commission',
            correlationId: $correlationId,
            metadata: [
                'proposal_id' => $proposal->id,
                'commission_rate' => self::COMMISSION_RATE,
                'original_price' => $proposal->price,
            ],
        );

        $this->audit->log(
            action: 'freelance_commission_deducted',
            subjectType: $proposal::class,
            subjectId: $proposal->id,
            old: [],
            new: [
                'commission_amount' => $commissionAmount,
                'freelancer_id' => $proposal->freelancer_id,
            ],
            correlationId: $correlationId,
        );

        $this->logger->info('Freelance proposal commission deducted', [
            'proposal_id' => $proposal->id,
            'freelancer_id' => $proposal->freelancer_id,
            'commission' => $commissionAmount,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Обработка сбоя — логирование ошибки.
     */
    public function failed(ProposalAccepted $event, \Throwable $exception): void
    {
        $this->logger->error('DeductProposalCommissionListener failed', [
            'event' => ProposalAccepted::class,
            'error' => $exception->getMessage(),
            'correlation_id' => $event->correlationId,
        ]);
    }
}
