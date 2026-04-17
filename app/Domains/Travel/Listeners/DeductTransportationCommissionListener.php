<?php declare(strict_types=1);

namespace App\Domains\Travel\Listeners;

use App\Models\BalanceTransaction;
use Illuminate\Queue\InteractsWithQueue;
use Psr\Log\LoggerInterface;
use Throwable;

final class DeductTransportationCommissionListener
{

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(object $event): void
    {
        $item = $event->transportation ?? $event->flight ?? null;
        $itemType = isset($event->flight) ? 'flight' : 'transportation';

        if ($item === null) {
            $this->logger->warning('Travel commission deduction skipped: missing event payload', [
                'correlation_id' => $event->correlationId ?? null,
            ]);
            return;
        }

        try {
            if (($item->agency ?? null) === null || ($item->agency->owner ?? null) === null) {
                $this->logger->warning('Agency owner not found for travel commission deduction', [
                    'item_id' => $item->id ?? null,
                    'item_type' => $itemType,
                    'correlation_id' => $event->correlationId ?? null,
                ]);
                return;
            }

            $wallet = $item->agency->owner->wallet;

            if ($wallet === null) {
                $this->logger->warning('Agency owner wallet not found', [
                    'item_id' => $item->id ?? null,
                    'item_type' => $itemType,
                    'correlation_id' => $event->correlationId ?? null,
                ]);
                return;
            }

            $commissionInCents = (int) (($item->commission_amount ?? 0) * 100);

            if ($commissionInCents <= 0) {
                return;
            }

            $wallet->decrement('current_balance', $commissionInCents);

            BalanceTransaction::create([
                'tenant_id' => $item->tenant_id,
                'wallet_id' => $wallet->id,
                'type' => 'commission',
                'amount' => $commissionInCents,
                'description' => "Commission for {$itemType} booking (#{$item->id})",
                'reference_type' => "travel_{$itemType}",
                'reference_id' => $item->id,
                'correlation_id' => $event->correlationId ?? null,
            ]);

            $this->logger->info("Travel {$itemType} commission deducted", [
                'item_id' => $item->id,
                'item_type' => $itemType,
                'agency_id' => $item->agency_id ?? null,
                'commission_amount' => $item->commission_amount ?? null,
                'correlation_id' => $event->correlationId ?? null,
                'wallet_id' => $wallet->id,
            ]);
        } catch (Throwable $e) {
            $this->logger->error("Travel {$itemType} commission deduction failed", [
                'item_id' => $item->id ?? null,
                'error' => $e->getMessage(),
                'correlation_id' => $event->correlationId ?? null,
            ]);

            throw $e;
        }
    }
}

