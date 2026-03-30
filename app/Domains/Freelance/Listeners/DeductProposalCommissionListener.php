<?php declare(strict_types=1);

namespace App\Domains\Freelance\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class DeductProposalCommissionListener extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use InteractsWithQueue;

        public function __construct(
            private readonly BalanceTransactionService $balanceService,
        ) {}

        public function handle(ProposalAccepted $event): void
        {
            DB::transaction(function () use ($event) {
                $proposal = $event->proposal->load('job');
                $client = $proposal->job->client;

                $proposedAmount = (int)($proposal->proposed_amount * 100);
                $commissionAmount = (int)($proposedAmount * 0.14);

                Log::channel('audit')->info('Freelance proposal accepted - deducting 14% commission', [
                    'proposal_id' => $proposal->id,
                    'freelancer_id' => $proposal->freelancer_id,
                    'client_id' => $client->id,
                    'proposed_amount' => $proposedAmount,
                    'commission_amount' => $commissionAmount,
                    'correlation_id' => $event->correlationId,
                ]);

                $this->balanceService->debit(
                    userId: $client->id,
                    amount: $commissionAmount,
                    type: 'commission',
                    reason: "Freelance proposal accepted - 14% platform commission",
                    sourceType: 'freelance_proposal',
                    sourceId: $proposal->id,
                    correlationId: $event->correlationId,
                );
            });
        }
}
