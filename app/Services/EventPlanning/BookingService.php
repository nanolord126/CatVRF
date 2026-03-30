<?php declare(strict_types=1);

namespace App\Services\EventPlanning;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BookingService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Create a formal booking for an event with financial oversight.
         * Includes: Prepayment logic, B2B multipliers, and idempotency.
         */
        public function createBooking(array $data, string $correlationId = null): EventBooking
        {
            $correlationId = $correlationId ?? (string) Str::uuid();

            // 1. Audit Start
            Log::channel('audit')->info('[EventBooking] Booking Creation Initiated', [
                'correlation_id' => $correlationId,
                'event_id' => $data['event_id'],
                'total_amount' => $data['total_amount'] ?? 0,
            ]);

            return DB::transaction(function () use ($data, $correlationId) {
                // 2. Fetch dependencies
                $event = EventProject::findOrFail($data['event_id']);
                $package = isset($data['package_id']) ? EventPackage::findOrFail($data['package_id']) : null;

                // 3. Prepayment Rule (Canon 2026: Mandatory 30% for B2C, Negotiable B2B)
                $prepaymentRatio = ($event->type === 'b2b') ? 0.20 : 0.30;
                $prepaymentAmount = (int) (($data['total_amount'] ?? 0) * $prepaymentRatio);

                // 4. Entity Assignment
                $booking = EventBooking::create([
                    'event_id' => $event->id,
                    'package_id' => $package ? $package->id : null,
                    'total_amount' => $data['total_amount'] ?? 0,
                    'prepayment_amount' => $prepaymentAmount,
                    'payment_status' => 'unpaid',
                    'expiry_at' => now()->addDays(3), // Standard 3-day deadline
                    'metadata' => array_merge($data['metadata'] ?? [], [
                        'prepayment_ratio' => $prepaymentRatio,
                        'is_b2b_multiplier_applied' => $event->type === 'b2b',
                    ]),
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('[EventBooking] Booking Created Successfully', [
                    'booking_uuid' => $booking->uuid,
                    'correlation_id' => $correlationId,
                    'prepayment_required' => $prepaymentAmount,
                ]);

                return $booking;
            });
        }

        /**
         * Mark booking as paid (partial or full).
         */
        public function processPayment(int $bookingId, int $amount, string $correlationId): bool
        {
            return DB::transaction(function () use ($bookingId, $amount, $correlationId) {
                $booking = EventBooking::lockForUpdate()->findOrFail($bookingId);

                $newStatus = ($amount >= $booking->total_amount) ? 'paid' : 'partial';

                $booking->update([
                    'payment_status' => $newStatus,
                    'metadata' => array_merge($booking->metadata ?? [], [
                        'last_payment_at' => now()->toIso8601String(),
                        'last_payment_amount' => $amount,
                    ]),
                    'correlation_id' => $correlationId,
                ]);

                // If paid, confirm the event
                if ($newStatus === 'paid') {
                     $booking->event->update(['status' => 'confirmed']);
                }

                Log::channel('audit')->info('[EventBooking] Payment Processed', [
                    'booking_uuid' => $booking->uuid,
                    'new_status' => $newStatus,
                    'correlation_id' => $correlationId,
                ]);

                return true;
            });
        }

        /**
         * Cancel booking with refund rules (Canon 2026: No refund if cancelled less than 7 days before).
         */
        public function cancelBooking(int $bookingId, string $correlationId): bool
        {
            return DB::transaction(function () use ($bookingId, $correlationId) {
                $booking = EventBooking::findOrFail($bookingId);
                $eventDate = $booking->event->event_date;

                $canRefund = $eventDate->diffInDays(now()) >= 7;

                $booking->update([
                    'payment_status' => $canRefund ? 'refunded' : 'cancelled_no_refund',
                    'correlation_id' => $correlationId,
                ]);

                $booking->event->update(['status' => 'cancelled']);

                Log::channel('audit')->warning('[EventBooking] Booking Cancelled', [
                    'uuid' => $booking->uuid,
                    'is_refundable' => $canRefund,
                    'correlation_id' => $correlationId,
                ]);

                return true;
            });
        }
}
