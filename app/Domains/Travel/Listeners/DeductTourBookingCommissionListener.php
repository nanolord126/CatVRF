<?php

declare(strict_types=1);

namespace App\Domains\Travel\Listeners;

use App\Models\BalanceTransaction;
use Illuminate\Queue\InteractsWithQueue;
use Psr\Log\LoggerInterface;
use Throwable;

final class DeductTourBookingCommissionListener
{

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(object $event): void
    {
        try {
            $wallet = null;

            if (isset($event->booking->agency_id)) {
                $wallet = \App\Models\Wallet::where('walletable_type', 'agency')
                    ->where('walletable_id', $event->booking->agency_id)
                    ->lockForUpdate()
                    ->first();
            }

            if ($wallet === null) {
                $this->logger->warning('Agency owner wallet not found', [
                    'booking_id' => $event->booking->id ?? null,
                    'correlation_id' => $event->correlationId ?? null,
                ]);
                return;
            }

            $commissionInCents = (int) ($event->booking->commission_amount * 100);

            $wallet->decrement('current_balance', $commissionInCents);

            BalanceTransaction::create([
                'tenant_id'      => $event->booking->tenant_id,
                'wallet_id'      => $wallet->id,
                'type'           => 'commission',
                'amount'         => $commissionInCents,
                'description'    => "Commission for tour booking #{$event->booking->booking_number}",
                'reference_type' => 'travel_booking',
                'reference_id'   => $event->booking->id,
                'correlation_id' => $event->correlationId ?? null,
            ]);

            $this->logger->info('Travel commission deducted', [
                'booking_id'       => $event->booking->id,
                'booking_number'   => $event->booking->booking_number,
                'agency_id'        => $event->booking->agency_id,
                'commission_amount' => $event->booking->commission_amount,
                'correlation_id'   => $event->correlationId ?? null,
                'wallet_id'        => $wallet->id,
            ]);
        } catch (Throwable $e) {
            $this->logger->error('Travel commission deduction failed', [
                'booking_id'     => $event->booking->id ?? null,
                'error'          => $e->getMessage(),
                'correlation_id' => $event->correlationId ?? null,
            ]);

            throw $e;
        }
    }
}