<?php declare(strict_types=1);

namespace App\Domains\ShortTermRentals\Jobs;
use Illuminate\Queue\InteractsWithQueue;




use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\InteractsWithQueue;
use Illuminate\Bus\Queueable;
final class StrPayoutJob
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public function __construct(private readonly int $bookingId,
            private ?string $correlationId = null,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly Request $request, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        public function handle(WalletService $walletService): void
        {
            $correlationId = $this->correlationId ?? (string) Str::uuid();

            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

            $this->db->transaction(function () use ($walletService, $correlationId) {
                $booking = StrBooking::lockForUpdate()->findOrFail($this->bookingId);

                if (!$booking->isReadyForPayout()) {
                    $this->logger->warning('ShortTermRental: Attempted payout for not ready booking', [
                        'booking_id' => $booking->id,
                        'status' => $booking->status,
                        'payout_at' => $booking->payout_at?->toIso8601String(),
                        'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                    ]);
                    return;
                }

                // Платформа берет 14% стандартную комиссию
                $commission = (int) ($booking->total_price * 0.14);
                $payoutAmount = $booking->total_price - $commission;

                // Зачисление на баланс тенанта/бизнес-группы
                $walletService->credit([
                    'tenant_id' => $booking->tenant_id,
                    'business_group_id' => $booking->business_group_id,
                    'booking_id' => $booking->id,
                    'payout_amount' => $payoutAmount,
                    'commission' => $commission,
                    'correlation_id' => $correlationId,
                ]);
            });
        }

        public function tags(): array
        {
            return ['short-term-rentals', 'payout', "booking:{$this->bookingId}"];
        }
}
