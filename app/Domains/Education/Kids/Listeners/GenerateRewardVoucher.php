<?php declare(strict_types=1);

namespace App\Domains\Education\Kids\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class GenerateRewardVoucher extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
