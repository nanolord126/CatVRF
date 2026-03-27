<?php

declare(strict_types=1);

namespace App\Domains\Education\Kids\Listeners;

use App\Domains\Education\Kids\Events\KidsProductPurchased;
use App\Domains\Education\Kids\Services\KidsVoucherService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * GenerateRewardVoucher - Auto-grants a voucher upon high-value children product purchases.
 * Rule: > 5000 RUB purchase rewards with 500 RUB trial pass.
 * Layer: Events & Listeners (7/9)
 */
final class GenerateRewardVoucher implements ShouldQueue
{
    public function __construct(
        private readonly KidsVoucherService $voucherService
    ) {}

    public function handle(KidsProductPurchased $event): void
    {
        Log::channel('audit')->info('Listener started: Reward Voucher Check', [
            'user_id' => $event->userId,
            'amount' => $event->amountKopecks,
            'correlation_id' => $event->correlationId,
        ]);

        if ($event->amountKopecks >= 500000) { // 5000 RUB
            $this->voucherService->issueGiftVoucher(
                userId: $event->userId,
                amountKopecks: 50000, // 500 RUB Reward
                correlationId: $event->correlationId
            );

            Log::channel('audit')->info('Reward voucher issued successfully.', [
                'user_id' => $event->userId,
                'correlation_id' => $event->correlationId,
            ]);
        }
    }
}
