<?php

declare(strict_types=1);

namespace App\Domains\Luxury\Jobs;

use App\Domains\Luxury\Models\VIPBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * CheckBookingPaymentTimeout
 *
 * Layer 7: Jobs Layer
 * Проверяет неоплаченные депозиты для VIP-бронирований и отменяет их по тайм-ауту.
 *
 * @version 1.0.0
 * @author CatVRF
 */
final class CheckBookingPaymentTimeout implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private string $bookingUuid,
        private string $correlationId
    ) {}

    /**
     * Выполнение джобы
     */
    public function handle(): void
    {
        try {
            $booking = VIPBooking::where('uuid', $this->bookingUuid)->first();

            if (!$booking || $booking->payment_status === 'paid' || $booking->status === 'cancelled') {
                return;
            }

            // Отмена бронирования, если депозит не был оплачен
            \Illuminate\Support\Facades\DB::transaction(function () use ($booking) {
                $booking->update([
                    'status' => 'cancelled',
                    'notes' => ($booking->notes ?? '') . ' [Auto-cancelled due to payment timeout]',
                    'correlation_id' => $this->correlationId,
                ]);

                // Возврат стока (если это товар)
                $bookable = $booking->bookable;
                if ($bookable instanceof \App\Domains\Luxury\Models\LuxuryProduct) {
                    $bookable->decrement('hold_stock');
                }

                Log::channel('audit')->info('VIP Booking Expired and Cancelled', [
                    'booking_uuid' => $booking->uuid,
                    'correlation_id' => $this->correlationId,
                ]);
            });

        } catch (Throwable $e) {
            Log::channel('audit')->error('VIP Booking Payment Timeout Error', [
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            throw $e; // Для retry
        }
    }
}
