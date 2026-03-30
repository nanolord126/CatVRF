<?php declare(strict_types=1);

namespace App\Domains\ShortTermRentals\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class StrPayoutJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public function __construct(
            public readonly int $bookingId,
            public readonly ?string $correlationId = null
        ) {}

        public function handle(WalletService $walletService): void
        {
            $correlationId = $this->correlationId ?? (string) Str::uuid();

            DB::transaction(function () use ($walletService, $correlationId) {
                $booking = StrBooking::lockForUpdate()->findOrFail($this->bookingId);

                if (!$booking->isReadyForPayout()) {
                    Log::channel('audit')->warning('ShortTermRental: Attempted payout for not ready booking', [
                        'booking_id' => $booking->id,
                        'status' => $booking->status,
                        'payout_at' => $booking->payout_at?->toIso8601String(),
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
                    'amount' => $payoutAmount,
                    'type' => 'payout',
                    'reason' => "Выплата за бронирование {$booking->uuid}",
                    'correlation_id' => $correlationId,
                ]);

                $booking->update([
                    'payout_at' => now(), // Фиксируем факт выплаты
                    'metadata' => array_merge($booking->metadata ?? [], [
                        'payout_amount' => $payoutAmount,
                        'commission_amount' => $commission,
                        'payout_processed_at' => now()->toIso8601String(),
                    ]),
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('ShortTermRental: Payout successful', [
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
