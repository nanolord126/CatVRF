<?php declare(strict_types=1);

namespace App\Domains\Sports\Fitness\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class DeductMembershipCommissionListener extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use InteractsWithQueue;

        public function handle(MembershipCreated $event): void
        {
            try {
                $gym = $event->membership->gym;
                $commissionAmount = (int) ($event->membership->commission_amount * 100);

                DB::transaction(function () use ($gym, $commissionAmount, $event) {
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

                    Log::channel('audit')->info('Membership commission deducted', [
                        'membership_id' => $event->membership->id,
                        'gym_id' => $gym->id,
                        'commission_amount' => $event->membership->commission_amount,
                        'correlation_id' => $event->correlationId,
                    ]);
                });
            } catch (Throwable $e) {
                Log::channel('audit')->error('Failed to deduct membership commission', [
                    'membership_id' => $event->membership->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $event->correlationId,
                ]);
                throw $e;
            }
        }
}
