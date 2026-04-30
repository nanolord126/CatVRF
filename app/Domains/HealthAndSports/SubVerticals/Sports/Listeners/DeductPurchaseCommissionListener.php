<?php

declare(strict_types=1);

namespace App\Domains\Sports\Listeners;

use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use App\Models\BalanceTransaction;
use App\Models\Wallet;
use Illuminate\Database\DatabaseManager;

final class DeductPurchaseCommissionListener
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly Guard $guard
    ) {}


    public function handle(PurchaseCreated $event): void
    {
        try {
            $this->logger->$this->logger->info('Processing purchase commission deduction', [
                'purchase_id' => $event->purchase->id,
                'commission_amount' => $event->purchase->commission_amount,
                'correlation_id' => $event->correlationId,
            ]);

            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

            $this->db->transaction(function () use ($event) {
                $wallet = Wallet::lockForUpdate()
                    ->where('tenant_id', $event->purchase->tenant_id)
                    ->firstOrFail();

                $wallet->decrement('balance', intval($event->purchase->commission_amount * 100));

                BalanceTransaction::create([
                    'wallet_id' => $wallet->id,
                    'type' => 'commission',
                    'amount' => intval($event->purchase->commission_amount * 100),
                    'status' => 'completed',
                    'correlation_id' => $event->correlationId,
                    'metadata' => [
                        'purchase_id' => $event->purchase->id,
                        'studio_id' => $event->purchase->studio_id,
                    ],
                ]);
            });

            $this->logger->$this->logger->info('Purchase commission deducted successfully', [
                'purchase_id' => $event->purchase->id,
                'correlation_id' => $event->correlationId,
            ]);
        } catch (Throwable $e) {
            $this->logger->error('Failed to deduct purchase commission', [
                'purchase_id' => $event->purchase->id,
                'error' => $e->getMessage(),
                'correlation_id' => $event->correlationId,
            ]);
            throw $e;
        }
    }
}
