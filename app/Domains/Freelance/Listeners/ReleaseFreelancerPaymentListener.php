<?php declare(strict_types=1);

namespace App\Domains\Freelance\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ReleaseFreelancerPaymentListener extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
