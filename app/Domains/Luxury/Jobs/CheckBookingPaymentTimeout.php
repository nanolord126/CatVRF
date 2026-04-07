<?php declare(strict_types=1);

/**
 * CheckBookingPaymentTimeout — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/checkbookingpaymenttimeout
 */


namespace App\Domains\Luxury\Jobs;


use Psr\Log\LoggerInterface;
final class CheckBookingPaymentTimeout
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public function __construct(private string $bookingUuid,
            private string $correlationId,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

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
                $this->db->transaction(function () use ($booking) {
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

                    $this->logger->info('VIP Booking Expired and Cancelled', [
                        'booking_uuid' => $booking->uuid,
                        'correlation_id' => $this->correlationId,
                    ]);
                });

            } catch (Throwable $e) {
                $this->logger->error('VIP Booking Payment Timeout Error', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $this->correlationId,
                ]);

                throw $e; // Для retry
            }
        }
}
