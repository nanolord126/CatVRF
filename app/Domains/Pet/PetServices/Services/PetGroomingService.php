<?php declare(strict_types=1);

namespace App\Domains\Pet\PetServices\Services;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class PetGroomingService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly WalletService $wallet,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly Guard $guard,
    ) {}

    /**
     * Создание записи на груминг.
     */
    public function createSession(
        int $groomerId,
        string $sessionDate,
        int $durationMinutes,
        string $petType,
        string $serviceType,
        int $priceKopecks,
        string $correlationId,
    ): PetGroomingSession {
        $this->fraud->check(
            userId: $this->guard->id() ?? 0,
            operationType: 'pet_grooming_book',
            amount: $priceKopecks,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($groomerId, $sessionDate, $durationMinutes, $petType, $serviceType, $priceKopecks, $correlationId): PetGroomingSession {
            $platformFee = (int) ($priceKopecks * 0.14);
            $payoutAmount = $priceKopecks - $platformFee;

            $session = PetGroomingSession::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => tenant()->id,
                'groomer_id' => $groomerId,
                'client_id' => $this->guard->id(),
                'session_date' => $sessionDate,
                'duration_minutes' => $durationMinutes,
                'pet_type' => $petType,
                'service_type' => $serviceType,
                'total_kopecks' => $priceKopecks,
                'platform_fee_kopecks' => $platformFee,
                'payout_kopecks' => $payoutAmount,
                'status' => 'pending_payment',
                'payment_status' => 'pending',
                'correlation_id' => $correlationId,
                'tags' => ['pet_type' => $petType, 'service' => $serviceType],
            ]);

            $this->logger->info('Pet grooming session booked', [
                'session_id' => $session->id,
                'session_uuid' => $session->uuid,
                'groomer_id' => $groomerId,
                'pet_type' => $petType,
                'service_type' => $serviceType,
                'total_kopecks' => $priceKopecks,
                'correlation_id' => $correlationId,
            ]);

            return $session;
        });
    }

    /**
     * Завершение сессии груминга и выплата грумеру.
     */
    public function completeSession(int $sessionId, string $correlationId): PetGroomingSession
    {
        $this->fraud->check(
            userId: $this->guard->id() ?? 0,
            operationType: 'pet_grooming_complete',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($sessionId, $correlationId): PetGroomingSession {
            $session = PetGroomingSession::lockForUpdate()->findOrFail($sessionId);

            if ($session->payment_status !== 'completed') {
                throw new \RuntimeException("Session {$sessionId} payment not completed.");
            }

            if ($session->status === 'completed') {
                throw new \RuntimeException("Session {$sessionId} already completed.");
            }

            $session->update([
                'status' => 'completed',
                'completed_at' => now(),
                'correlation_id' => $correlationId,
            ]);

            $this->wallet->credit(
                userId: $session->groomer_id,
                amount: $session->payout_kopecks,
                type: 'grooming_payout',
                reason: "Grooming session #{$session->id} completed",
                correlationId: $correlationId,
            );

            $this->logger->info('Pet grooming session completed with payout', [
                'session_id' => $session->id,
                'payout_kopecks' => $session->payout_kopecks,
                'groomer_id' => $session->groomer_id,
                'correlation_id' => $correlationId,
            ]);

            return $session->refresh();
        });
    }

    /**
     * Отмена записи на груминг с возвратом средств.
     */
    public function cancelSession(int $sessionId, string $reason, string $correlationId): PetGroomingSession
    {
        return $this->db->transaction(function () use ($sessionId, $reason, $correlationId): PetGroomingSession {
            $session = PetGroomingSession::lockForUpdate()->findOrFail($sessionId);

            if ($session->status === 'completed') {
                throw new \RuntimeException("Cannot cancel a completed grooming session.");
            }

            if ($session->payment_status === 'completed') {
                $this->wallet->credit(
                    userId: $session->client_id,
                    amount: $session->total_kopecks,
                    type: 'grooming_refund',
                    reason: "Grooming session #{$session->id} cancelled: {$reason}",
                    correlationId: $correlationId,
                );
            }

            $session->update([
                'status' => 'cancelled',
                'payment_status' => $session->payment_status === 'completed' ? 'refunded' : $session->payment_status,
                'cancellation_reason' => $reason,
                'correlation_id' => $correlationId,
            ]);

            $this->logger->info('Pet grooming session cancelled', [
                'session_id' => $session->id,
                'reason' => $reason,
                'correlation_id' => $correlationId,
            ]);

            return $session->refresh();
        });
    }
}
