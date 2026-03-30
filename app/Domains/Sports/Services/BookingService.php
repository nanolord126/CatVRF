<?php declare(strict_types=1);

namespace App\Domains\Sports\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BookingService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function createBooking(
            int $classId,
            int $memberId,
            ?int $trainerId,
            string $type,
            float $price,
            bool $isTrial = false,
            ?string $correlationId = null,
        ): Booking {
            $correlationId = Str::uuid()->toString();
            Log::channel('audit')->info('Service method called in Sports', ['correlation_id' => $correlationId]);

            try {
                $correlationId = $correlationId ?? Str::uuid()->toString();

                Log::channel('audit')->info('Creating booking', [
                    'class_id' => $classId,
                    'member_id' => $memberId,
                    'type' => $type,
                    'correlation_id' => $correlationId,
                ]);

                $booking = DB::transaction(function () use (
                    $classId,
                    $memberId,
                    $trainerId,
                    $type,
                    $price,
                    $isTrial,
                    $correlationId,
                ) {
                    $classSession = ClassSession::findOrFail($classId);

                    if ($classSession->enrolled_count >= $classSession->max_participants && !$isTrial) {
                        throw new \Exception('Class is full');
                    }

                    $classSession->increment('enrolled_count');

                    return Booking::create([
                        'tenant_id' => tenant('id'),
                        'class_id' => $classId,
                        'member_id' => $memberId,
                        'trainer_id' => $trainerId,
                        'type' => $type,
                        'status' => 'pending',
                        'price' => $price,
                        'is_trial' => $isTrial,
                        'payment_status' => false,
                        'correlation_id' => $correlationId,
                    ]);
                });

                Log::channel('audit')->info('Booking created', [
                    'booking_id' => $booking->id,
                    'correlation_id' => $correlationId,
                ]);

                return $booking;
            } catch (Throwable $e) {
                Log::channel('audit')->error('Failed to create booking', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId ?? null,
                ]);
                throw $e;
            }
        }

        public function confirmBooking(Booking $booking, ?string $correlationId = null): void
        {
            $correlationId = Str::uuid()->toString();
            Log::channel('audit')->info('Service method called in Sports', ['correlation_id' => $correlationId]);

            try {
                $correlationId = $correlationId ?? Str::uuid()->toString();

                $booking->update([
                    'status' => 'confirmed',
                    'payment_status' => true,
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('Booking confirmed', [
                    'booking_id' => $booking->id,
                    'correlation_id' => $correlationId,
                ]);
            } catch (Throwable $e) {
                Log::channel('audit')->error('Failed to confirm booking', [
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }

        public function cancelBooking(Booking $booking, string $reason = '', ?string $correlationId = null): void
        {
            $correlationId = Str::uuid()->toString();
            Log::channel('audit')->info('Service method called in Sports', ['correlation_id' => $correlationId]);

            try {
                $correlationId = $correlationId ?? Str::uuid()->toString();

                DB::transaction(function () use ($booking, $reason, $correlationId) {
                    $booking->update([
                        'status' => 'cancelled',
                        'notes' => $reason,
                        'correlation_id' => $correlationId,
                    ]);

                    $booking->class->decrement('enrolled_count');
                });

                Log::channel('audit')->info('Booking cancelled', [
                    'booking_id' => $booking->id,
                    'correlation_id' => $correlationId,
                ]);
            } catch (Throwable $e) {
                Log::channel('audit')->error('Failed to cancel booking', [
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }

        public function markAsAttended(Booking $booking, ?string $correlationId = null): void
        {
            $correlationId = Str::uuid()->toString();
            Log::channel('audit')->info('Service method called in Sports', ['correlation_id' => $correlationId]);

            try {
                $correlationId = $correlationId ?? Str::uuid()->toString();

                $booking->update([
                    'status' => 'completed',
                    'attended_at' => now(),
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('Booking marked as attended', [
                    'booking_id' => $booking->id,
                    'correlation_id' => $correlationId,
                ]);
            } catch (Throwable $e) {
                Log::channel('audit')->error('Failed to mark booking as attended', [
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }
}
