<?php declare(strict_types=1);

namespace App\Domains\Education\Courses\Listeners;

use Carbon\Carbon;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final class DeductEnrollmentCommissionListener
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}


    public function handle(EnrollmentCreated $event): void
        {
            try {
                $this->logger->info('Deducting enrollment commission', [
                    'enrollment_id' => $event->enrollment->id,
                    'correlation_id' => $event->correlationId,
                    'amount' => $event->enrollment->commission_price,
                ]);

                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

                $this->db->transaction(function () use ($event) {
                    // Deduct 14% commission from instructor wallet/balance
                    $instructorWallet = $event->enrollment->course->instructor_id
                        ? $this->db->table('wallets')->where('user_id', $event->enrollment->course->instructor_id)->first()
                        : null;

                    if ($instructorWallet) {
                        $this->db->table('wallets')
                            ->where('id', $instructorWallet->id)
                            ->update(['balance' => $this->db->raw("balance - {$event->enrollment->commission_price}")]);
                    }

                    $this->logger->info('Enrollment commission deducted', [
                        'enrollment_id' => $event->enrollment->id,
                        'correlation_id' => $event->correlationId,
                    ]);
                });
            } catch (Throwable $e) {
                $this->logger->error('Failed to deduct enrollment commission', [
                    'enrollment_id' => $event->enrollment->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $event->correlationId,
                ]);
                throw $e;
            }
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => Carbon::now()->toIso8601String(),
        ];
    }
}
