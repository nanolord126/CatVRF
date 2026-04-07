<?php declare(strict_types=1);

namespace App\Domains\PersonalDevelopment\Services;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class BookingService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly PricingService $pricingService,
        private readonly WalletService $walletService,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly Guard $guard,
    ) {}

    /**
     * Бронирование участия в программе личного развития.
     */
    public function bookProgram(int $programId, int $userId, string $correlationId): Enrollment
    {
        $this->fraud->check(
            userId: $this->guard->id() ?? 0,
            operationType: 'pd_booking',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($programId, $userId, $correlationId): Enrollment {
            $program = Program::lockForUpdate()->findOrFail($programId);

            if ($program->status !== 'active') {
                throw new \RuntimeException("Program {$programId} is not available for enrollment.");
            }

            $currentEnrollments = Enrollment::where('program_id', $programId)
                ->whereNotIn('status', ['cancelled', 'expired'])
                ->count();

            if ($program->max_participants > 0 && $currentEnrollments >= $program->max_participants) {
                throw new \RuntimeException("Program {$programId} is fully booked.");
            }

            $existingEnrollment = Enrollment::where('program_id', $programId)
                ->where('user_id', $userId)
                ->whereNotIn('status', ['cancelled', 'expired'])
                ->first();

            if ($existingEnrollment !== null) {
                throw new \RuntimeException("User {$userId} is already enrolled in program {$programId}.");
            }

            $user = \App\Models\User::findOrFail($userId);
            $finalPrice = $this->pricingService->calculateFinalPrice($program, $user);

            $enrollment = Enrollment::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $program->tenant_id,
                'program_id' => $programId,
                'user_id' => $userId,
                'price_kopecks' => $finalPrice,
                'status' => 'pending_payment',
                'progress_percent' => 0,
                'correlation_id' => $correlationId,
                'tags' => ['source' => 'platform_booking'],
            ]);

            $this->logger->info('PD Booking: Enrollment created', [
                'enrollment_uuid' => $enrollment->uuid,
                'program_id' => $programId,
                'user_id' => $userId,
                'price_kopecks' => $finalPrice,
                'correlation_id' => $correlationId,
            ]);

            return $enrollment;
        });
    }

    /**
     * Подтверждение оплаты и активация записи.
     */
    public function confirmPayment(int $enrollmentId, string $correlationId): Enrollment
    {
        $this->fraud->check(
            userId: $this->guard->id() ?? 0,
            operationType: 'pd_payment_confirm',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($enrollmentId, $correlationId): Enrollment {
            $enrollment = Enrollment::lockForUpdate()->findOrFail($enrollmentId);

            if ($enrollment->status !== 'pending_payment') {
                throw new \RuntimeException("Enrollment {$enrollmentId} is not awaiting payment.");
            }

            $enrollment->update([
                'status' => 'active',
                'activated_at' => now(),
                'correlation_id' => $correlationId,
            ]);

            $this->logger->info('PD Booking: Payment confirmed, enrollment active', [
                'enrollment_uuid' => $enrollment->uuid,
                'enrollment_id' => $enrollmentId,
                'correlation_id' => $correlationId,
            ]);

            return $enrollment->refresh();
        });
    }

    /**
     * Отмена бронирования с возвратом средств.
     */
    public function cancelBooking(int $enrollmentId, string $reason, string $correlationId): Enrollment
    {
        $this->fraud->check(
            userId: $this->guard->id() ?? 0,
            operationType: 'pd_booking_cancel',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($enrollmentId, $reason, $correlationId): Enrollment {
            $enrollment = Enrollment::lockForUpdate()->findOrFail($enrollmentId);

            if ($enrollment->status === 'completed') {
                throw new \RuntimeException("Cannot cancel a completed enrollment.");
            }

            if ($enrollment->status === 'active' && $enrollment->price_kopecks > 0) {
                $this->walletService->credit(
                    userId: $enrollment->user_id,
                    amount: $enrollment->price_kopecks,
                    type: 'pd_refund',
                    reason: "Refund for enrollment #{$enrollment->id}: {$reason}",
                    correlationId: $correlationId,
                );
            }

            $enrollment->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'cancelled_at' => now(),
                'correlation_id' => $correlationId,
            ]);

            $this->logger->info('PD Booking: Enrollment cancelled', [
                'enrollment_uuid' => $enrollment->uuid,
                'reason' => $reason,
                'correlation_id' => $correlationId,
            ]);

            return $enrollment->refresh();
        });
    }

    /**
     * Получение активных записей пользователя.
     */
    public function getUserEnrollments(int $userId): \Illuminate\Support\Collection
    {
        return Enrollment::where('user_id', $userId)
            ->whereIn('status', ['active', 'pending_payment'])
            ->with('program')
            ->orderByDesc('created_at')
            ->get();
    }
}
