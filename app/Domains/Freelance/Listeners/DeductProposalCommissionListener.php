declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Freelance\Listeners;

use App\Domains\Freelance\Events\ProposalAccepted;
use App\Services\Wallet\BalanceTransactionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final /**
 * DeductProposalCommissionListener
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class DeductProposalCommissionListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly BalanceTransactionService $balanceService,
    ) {
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}

    public function handle(ProposalAccepted $event): void
    {
        $this->db->transaction(function () use ($event) {
            $proposal = $event->proposal->load('job');
            $client = $proposal->job->client;

            $proposedAmount = (int)($proposal->proposed_amount * 100);
            $commissionAmount = (int)($proposedAmount * 0.14);

            $this->log->channel('audit')->info('Freelance proposal accepted - deducting 14% commission', [
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
