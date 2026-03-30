<?php declare(strict_types=1);

namespace App\Domains\Hotels\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class HotelPayoutJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public function __construct(
            public readonly int $bookingId,
            public readonly string $correlationId
        ) {}

        public function handle(WalletService $wallet): void
        {
            Log::channel('audit')->info('Hotel Payout Job Started', [
                'booking_id' => $this->bookingId,
                'correlation_id' => $this->correlationId,
            ]);

            $booking = Booking::findOrFail($this->bookingId);

            // ПРОВЕРКИ
            if ($booking->payment_status !== 'paid' || $booking->status === 'cancelled') {
                Log::error('Cannot payout for unpaid or cancelled booking', [
                    'booking_id' => $this->bookingId,
                    'correlation_id' => $this->correlationId,
                ]);
                return;
            }

            DB::transaction(function () use ($booking, $wallet) {
                // КОМИССИЯ
                $commission = (int) ($booking->total_price * 0.14); // 14% стандарт
                $payoutAmount = $booking->total_price - $commission;

                // CREDIT WALLET (Hotel / Business Group)
                $wallet->credit(
                    walletId: $booking->business_group_id, // Упрощенно: ID кошелька = ID бизнес-группы
                    amount: $payoutAmount,
                    type: 'payout',
                    correlationId: $this->correlationId,
                    metadata: [
                        'source' => 'hotel_booking',
                        'booking_uuid' => $booking->uuid,
                        'commission' => $commission,
                    ]
                );

                $booking->update([
                    'status' => 'completed',
                    'payout_at' => now(),
                ]);

                Log::channel('audit')->info('Hotel Payout Successful', [
                    'booking_id' => $this->bookingId,
                    'payout_amount' => $payoutAmount,
                    'correlation_id' => $this->correlationId,
                ]);
            });
        }

        public function tags(): array
        {
            return ['hotel', 'payout', 'booking:' . $this->bookingId];
        }
}
