<?php declare(strict_types=1);

namespace App\Domains\Sports\Services;




use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
final readonly class BookingService
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly Request $request, private readonly LoggerInterface $logger, private readonly Guard $guard) {}


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
            $this->logger->info('Service method called in Sports', ['correlation_id' => $correlationId]);

            try {
                $correlationId = $correlationId ?? Str::uuid()->toString();

                $this->logger->info('Creating booking', [
                    'class_id' => $classId,
                    'member_id' => $memberId,
                    'type' => $type,
                    'correlation_id' => $correlationId,
                ]);

                $booking = $this->db->transaction(function () use (
                    $classId,
                    $memberId,
                    $trainerId,
                    $type,
                    $price,
                    $isTrial,
                    $correlationId) {
                    $classSession = ClassSession::findOrFail($classId);

                    if ($classSession->enrolled_count >= $classSession->max_participants && !$isTrial) {
                        throw new \DomainException('Class is full');
                    }

                    $classSession->increment('enrolled_count');

                    return Booking::create([
                        'tenant_id' => tenant()?->id,
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

                $this->logger->info('Booking created', [
                    'booking_id' => $booking->id,
                    'correlation_id' => $correlationId,
                ]);

                return $booking;
            } catch (Throwable $e) {
                $this->logger->error('Failed to create booking', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId ?? null,
                ]);
                throw $e;
            }
        }

        public function confirmBooking(Booking $booking, ?string $correlationId = null): void
        {
            $correlationId = Str::uuid()->toString();
            $this->logger->info('Service method called in Sports', ['correlation_id' => $correlationId]);

            try {
                $correlationId = $correlationId ?? Str::uuid()->toString();

                $booking->update([
                    'status' => 'confirmed',
                    'payment_status' => true,
                    'correlation_id' => $correlationId,
                ]);

                $this->logger->info('Booking confirmed', [
                    'booking_id' => $booking->id,
                    'correlation_id' => $correlationId,
                ]);
            } catch (Throwable $e) {
                $this->logger->error('Failed to confirm booking', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                throw $e;
            }
        }

        public function cancelBooking(Booking $booking, string $reason = '', ?string $correlationId = null): void
        {
            $correlationId = Str::uuid()->toString();
            $this->logger->info('Service method called in Sports', ['correlation_id' => $correlationId]);

            try {
                $correlationId = $correlationId ?? Str::uuid()->toString();

                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

                $this->db->transaction(function () use ($booking, $reason, $correlationId) {
                    $booking->update([
                        'status' => 'cancelled',
                        'notes' => $reason,
                        'correlation_id' => $correlationId,
                    ]);

                    $booking->class->decrement('enrolled_count');
                });

                $this->logger->info('Booking cancelled', [
                    'booking_id' => $booking->id,
                    'correlation_id' => $correlationId,
                ]);
            } catch (Throwable $e) {
                $this->logger->error('Failed to cancel booking', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                throw $e;
            }
        }

        public function markAsAttended(Booking $booking, ?string $correlationId = null): void
        {
            $correlationId = Str::uuid()->toString();
            $this->logger->info('Service method called in Sports', ['correlation_id' => $correlationId]);

            try {
                $correlationId = $correlationId ?? Str::uuid()->toString();

                $booking->update([
                    'status' => 'completed',
                    'attended_at' => now(),
                    'correlation_id' => $correlationId,
                ]);

                $this->logger->info('Booking marked as attended', [
                    'booking_id' => $booking->id,
                    'correlation_id' => $correlationId,
                ]);
            } catch (Throwable $e) {
                $this->logger->error('Failed to mark booking as attended', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                throw $e;
            }
        }
}
