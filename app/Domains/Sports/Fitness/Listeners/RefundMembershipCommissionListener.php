<?php

declare(strict_types=1);


namespace App\Domains\Sports\Fitness\Listeners;

use App\Domains\Sports\Fitness\Events\MembershipExpired;
use App\Models\Wallet;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final /**
 * RefundMembershipCommissionListener
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class RefundMembershipCommissionListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(MembershipExpired $event): void
    {
        try {
            if ($event->membership->status !== 'cancelled') {
                return;
            }

            $gym = $event->membership->gym;
            $commissionAmount = (int) ($event->membership->commission_amount * 100);

            DB::transaction(function () use ($gym, $commissionAmount, $event) {
                $wallet = Wallet::where('tenant_id', $gym->tenant_id)->lockForUpdate()->first();
                if ($wallet) {
                    $wallet->increment('balance', $commissionAmount);
                }

                \App\Models\BalanceTransaction::create([
                    'wallet_id' => $wallet->id ?? null,
                    'tenant_id' => $gym->tenant_id,
                    'type' => 'refund',
                    'amount' => $commissionAmount,
                    'description' => "Membership commission refund #{$event->membership->id}",
                    'correlation_id' => $event->correlationId,
                ]);

                Log::channel('audit')->info('Membership commission refunded', [
                    'membership_id' => $event->membership->id,
                    'gym_id' => $gym->id,
                    'commission_amount' => $event->membership->commission_amount,
                    'correlation_id' => $event->correlationId,
                ]);
            });
        } catch (Throwable $e) {
            Log::channel('audit')->error('Failed to refund membership commission', [
                'membership_id' => $event->membership->id,
                'error' => $e->getMessage(),
                'correlation_id' => $event->correlationId,
            ]);
            throw $e;
        }
    }
}
