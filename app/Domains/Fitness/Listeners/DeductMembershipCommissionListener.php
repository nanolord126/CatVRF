declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Fitness\Listeners;

use App\Domains\Fitness\Events\MembershipCreated;
use App\Models\Wallet;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final /**
 * DeductMembershipCommissionListener
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class DeductMembershipCommissionListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(MembershipCreated $event): void
    {
        try {
            $gym = $event->membership->gym;
            $commissionAmount = (int) ($event->membership->commission_amount * 100);

            $this->db->transaction(function () use ($gym, $commissionAmount, $event) {
                $wallet = Wallet::where('tenant_id', $gym->tenant_id)->lockForUpdate()->first();
                if ($wallet) {
                    $wallet->decrement('balance', $commissionAmount);
                }

                \App\Models\BalanceTransaction::create([
                    'wallet_id' => $wallet->id ?? null,
                    'tenant_id' => $gym->tenant_id,
                    'type' => 'commission',
                    'amount' => -$commissionAmount,
                    'description' => "Membership commission #{$event->membership->id}",
                    'correlation_id' => $event->correlationId,
                ]);

                $this->log->channel('audit')->info('Membership commission deducted', [
                    'membership_id' => $event->membership->id,
                    'gym_id' => $gym->id,
                    'commission_amount' => $event->membership->commission_amount,
                    'correlation_id' => $event->correlationId,
                ]);
            });
        } catch (Throwable $e) {
            $this->log->channel('audit')->error('Failed to deduct membership commission', [
                'membership_id' => $event->membership->id,
                'error' => $e->getMessage(),
                'correlation_id' => $event->correlationId,
            ]);
            throw $e;
        }
    }
}
