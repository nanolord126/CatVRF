<?php

declare(strict_types=1);


namespace App\Domains\Freelance\Listeners;

use App\Domains\Freelance\Events\PaymentMilestoneReleased;
use App\Services\Wallet\BalanceTransactionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final /**
 * ReleaseFreelancerPaymentListener
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ReleaseFreelancerPaymentListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly BalanceTransactionService $balanceService,
    ) {}

    public function handle(PaymentMilestoneReleased $event): void
    {
        DB::transaction(function () use ($event) {
            $contract = $event->contract->load('freelancer', 'client');
            $freelancer = $contract->freelancer;

            $amountInCents = (int)($event->amount * 100);

            Log::channel('audit')->info('Freelance payment milestone released to freelancer', [
                'contract_id' => $contract->id,
                'freelancer_id' => $freelancer->id,
                'client_id' => $contract->client_id,
                'milestone_number' => $event->milestoneNumber,
                'amount' => $amountInCents,
                'correlation_id' => $event->correlationId,
            ]);

            $this->balanceService->credit(
                userId: $freelancer->user_id,
                amount: $amountInCents,
                type: 'freelance_payment',
                reason: "Milestone {$event->milestoneNumber} payment released",
                sourceType: 'freelance_contract',
                sourceId: $contract->id,
                correlationId: $event->correlationId,
            );
        });
    }
}
