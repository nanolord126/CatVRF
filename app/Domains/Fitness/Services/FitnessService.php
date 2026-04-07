<?php declare(strict_types=1);

namespace App\Domains\Fitness\Services;

use Carbon\Carbon;


use Psr\Log\LoggerInterface;
use App\Domains\Fitness\Models\Gym;
use App\Domains\Fitness\Models\Membership;
use App\Domains\Fitness\Models\Session;
use App\Domains\Fitness\Models\Trainer;
use App\Domains\Fitness\Models\WorkoutPlan;
use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Str;

/**
 * Главный сервис вертикали Fitness.
 * Layer 3 — Services. Канон CatVRF 2026.
 *
 * Управление абонементами, записями к тренеру, планами тренировок.
 * B2C: розничные абонементы. B2B: корпоративные пакеты.
 */
final readonly class FitnessService
{
    public function __construct(private FraudControlService $fraud,
        private WalletService       $wallet,
        private AuditService        $audit,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

    /**
     * Купить абонемент (B2C / B2B).
     */
    public function purchaseMembership(
        int    $userId,
        int    $gymId,
        string $type,
        int    $durationDays,
        int    $priceKopecks,
        bool   $isB2B = false,
        string $correlationId = '',
    ): Membership {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        $this->fraud->check(
            userId: $userId,
            operationType: 'fitness_membership_purchase',
            amount: $priceKopecks,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($userId, $gymId, $type, $durationDays, $priceKopecks, $isB2B, $correlationId) {
            $gym = Gym::findOrFail($gymId);

            $membership = Membership::create([
                'tenant_id'         => $gym->tenant_id,
                'business_group_id' => $gym->business_group_id,
                'gym_id'            => $gymId,
                'user_id'           => $userId,
                'type'              => $type,
                'duration_days'     => $durationDays,
                'price'             => $priceKopecks,
                'started_at'        => Carbon::now(),
                'expires_at'        => Carbon::now()->addDays($durationDays),
                'is_active'         => true,
                'correlation_id'    => $correlationId,
                'tags'              => ['b2b' => $isB2B],
            ]);

            $this->logger->info('Fitness membership purchased', [
                'user_id'        => $userId,
                'gym_id'         => $gymId,
                'type'           => $type,
                'is_b2b'         => $isB2B,
                'price'          => $priceKopecks,
                'correlation_id' => $correlationId,
                'tenant_id'      => $gym->tenant_id,
            ]);

            return $membership;
        });
    }

    /**
     * Записаться к тренеру.
     */
    public function bookSession(
        int    $userId,
        int    $trainerId,
        int    $gymId,
        string $scheduledAt,
        int    $durationMinutes = 60,
        string $correlationId = '',
    ): Session {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        $this->fraud->check(
            userId: $userId,
            operationType: 'fitness_session_booking',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($userId, $trainerId, $gymId, $scheduledAt, $durationMinutes, $correlationId) {
            $gym = Gym::findOrFail($gymId);

            $session = Session::create([
                'tenant_id'        => $gym->tenant_id,
                'business_group_id' => $gym->business_group_id,
                'gym_id'           => $gymId,
                'trainer_id'       => $trainerId,
                'user_id'          => $userId,
                'scheduled_at'     => $scheduledAt,
                'duration_minutes' => $durationMinutes,
                'status'           => 'confirmed',
                'type'             => 'personal',
                'correlation_id'   => $correlationId,
            ]);

            $this->logger->info('Fitness session booked', [
                'user_id'        => $userId,
                'trainer_id'     => $trainerId,
                'gym_id'         => $gymId,
                'scheduled_at'   => $scheduledAt,
                'correlation_id' => $correlationId,
                'tenant_id'      => $gym->tenant_id,
            ]);

            return $session;
        });
    }

    /**
     * Список клубов для пользователя (tenant-scoped).
     */
    public function listGyms(array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = Gym::where('is_active', true);

        if (isset($filters['city'])) {
            $query->where('address', 'like', '%' . $filters['city'] . '%');
        }

        return $query->get();
    }

    /**
     * Список тренеров клуба.
     */
    public function listTrainers(int $gymId): \Illuminate\Database\Eloquent\Collection
    {
        return Trainer::where('gym_id', $gymId)
            ->where('is_active', true)
            ->get();
    }
}
