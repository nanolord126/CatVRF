<?php

namespace App\Domains\Sports\Services;

use App\Domains\Sports\Models\Gym;
use App\Domains\Sports\Models\GymMembership;
use App\Domains\Sports\Models\GymMembershipHolder;
use App\Domains\Sports\Models\GymAttendanceLog;
use App\Domains\Finances\Services\PaymentService;
use App\Domains\Finances\Services\WalletService;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;
use Throwable;

/**
 * Сервис управления спортивными залами и членствами.
 */
class SportsService
{
    public function __construct(
        private PaymentService $paymentService,
        private WalletService $walletService
    ) {}

    /**
     * Создать членство пользователя в зале.
     * 
     * @param User $user
     * @param Gym $gym
     * @param GymMembership $membership
     * @param string $paymentMethod wallet|card
     * @return array
     */
    public function createMembership(User $user, Gym $gym, GymMembership $membership, string $paymentMethod = 'wallet'): array
    {
        DB::beginTransaction();
        try {
            $correlationId = Str::uuid()->toString();
            $expiresAt = now()->addMonths($membership->duration_months);

            // Попытка оплаты
            if ($paymentMethod === 'wallet') {
                $result = $this->walletService->debit(
                    $user,
                    $membership->price,
                    "Членство: {$gym->name} ({$membership->name})",
                    $gym->id,
                    $correlationId
                );

                if (!$result) {
                    throw new Exception('Недостаточно средств в кошельке');
                }
            } else {
                // Оплата картой через платёжный шлюз
                $paymentResult = $this->paymentService->initPayment([
                    'amount' => $membership->price,
                    'order_id' => "GYM-{$gym->id}-" . time(),
                    'user_id' => $user->id,
                    'description' => "Членство: {$gym->name}",
                ]);

                if ($paymentResult['status'] !== 'pending') {
                    throw new Exception('Ошибка при инициализации платежа');
                }
            }

            // Создать запись о членстве
            $holder = GymMembershipHolder::create([
                'user_id' => $user->id,
                'membership_id' => $membership->id,
                'gym_id' => $gym->id,
                'starts_at' => now(),
                'expires_at' => $expiresAt,
                'is_active' => true,
                'paid_amount' => $membership->price,
                'metadata' => [
                    'payment_method' => $paymentMethod,
                    'correlation_id' => $correlationId,
                ],
            ]);

            // Обновить счётчик членов в зале
            $gym->increment('total_members');

            // Создать аудит запись
            AuditLog::create([
                'tenant_id' => $user->tenant_id,
                'user_id' => $user->id,
                'model' => GymMembershipHolder::class,
                'model_id' => $holder->id,
                'action' => 'created',
                'changes' => [
                    'gym_id' => $gym->id,
                    'membership_id' => $membership->id,
                    'expires_at' => $expiresAt,
                ],
                'correlation_id' => $correlationId,
            ]);

            Log::channel('sports')->info('Membership created', [
                'user_id' => $user->id,
                'gym_id' => $gym->id,
                'membership_id' => $membership->id,
                'expires_at' => $expiresAt,
                'correlation_id' => $correlationId,
            ]);

            DB::commit();

            return [
                'success' => true,
                'holder_id' => $holder->id,
                'expires_at' => $expiresAt,
                'correlation_id' => $correlationId,
            ];
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Failed to create membership', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'gym_id' => $gym->id,
            ]);
            throw $e;
        }
    }

    /**
     * Зарегистрировать вход пользователя в зал.
     * 
     * @param User $user
     * @param Gym $gym
     * @return array
     */
    public function checkIn(User $user, Gym $gym): array
    {
        try {
            // Проверить активное членство
            $activeMembership = GymMembershipHolder::where('user_id', $user->id)
                ->where('gym_id', $gym->id)
                ->where('is_active', true)
                ->where('expires_at', '>', now())
                ->first();

            if (!$activeMembership) {
                throw new Exception('Членство не найдено или истекло');
            }

            $log = GymAttendanceLog::recordCheckIn($user->id, $gym->id);

            if (!$log) {
                throw new Exception('Ошибка при регистрации входа');
            }

            Log::channel('sports')->info('User checked in', [
                'user_id' => $user->id,
                'gym_id' => $gym->id,
                'membership_id' => $activeMembership->id,
            ]);

            return [
                'success' => true,
                'log_id' => $log->id,
                'message' => "Добро пожаловать в {$gym->name}!",
            ];
        } catch (Exception $e) {
            Log::error('Failed to check in', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);
            throw $e;
        }
    }

    /**
     * Зарегистрировать выход пользователя из зала.
     * 
     * @param User $user
     * @param Gym $gym
     * @return array
     */
    public function checkOut(User $user, Gym $gym): array
    {
        try {
            $log = GymAttendanceLog::recordCheckOut($user->id, $gym->id);

            if (!$log) {
                throw new Exception('Вход не найден');
            }

            // Получить длительность сессии
            $lastCheckIn = GymAttendanceLog::where('user_id', $user->id)
                ->where('gym_id', $gym->id)
                ->where('is_checkout', false)
                ->where('checked_at', '<', $log->checked_at)
                ->latest('checked_at')
                ->first();

            $sessionDuration = $lastCheckIn
                ? $lastCheckIn->checked_at->diffInMinutes($log->checked_at)
                : 0;

            Log::channel('sports')->info('User checked out', [
                'user_id' => $user->id,
                'gym_id' => $gym->id,
                'session_duration' => $sessionDuration,
            ]);

            return [
                'success' => true,
                'log_id' => $log->id,
                'session_duration_minutes' => $sessionDuration,
                'message' => "Спасибо за посещение! Вы провели $sessionDuration минут в зале.",
            ];
        } catch (Exception $e) {
            Log::error('Failed to check out', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);
            throw $e;
        }
    }

    /**
     * Пролонгировать членство пользователя.
     * 
     * @param GymMembershipHolder $holder
     * @param GymMembership $newMembership
     * @param string $paymentMethod
     * @return array
     */
    public function extendMembership(GymMembershipHolder $holder, GymMembership $newMembership, string $paymentMethod = 'wallet'): array
    {
        DB::beginTransaction();
        try {
            $user = $holder->user;
            $correlationId = Str::uuid()->toString();

            // Обработка платежа
            if ($paymentMethod === 'wallet') {
                $result = $this->walletService->debit(
                    $user,
                    $newMembership->price,
                    "Продление членства: {$holder->gym->name}",
                    $holder->gym->id,
                    $correlationId
                );

                if (!$result) {
                    throw new Exception('Недостаточно средств в кошельке');
                }
            } else {
                $paymentResult = $this->paymentService->initPayment([
                    'amount' => $newMembership->price,
                    'order_id' => "GYM-EXTEND-{$holder->id}-" . time(),
                    'user_id' => $user->id,
                    'description' => "Продление членства",
                ]);

                if ($paymentResult['status'] !== 'pending') {
                    throw new Exception('Ошибка при инициализации платежа');
                }
            }

            // Вычислить новую дату истечения
            $newExpiresAt = $holder->expires_at->isAfter(now())
                ? $holder->expires_at->addMonths($newMembership->duration_months)
                : now()->addMonths($newMembership->duration_months);

            // Обновить членство
            $holder->update([
                'membership_id' => $newMembership->id,
                'expires_at' => $newExpiresAt,
                'is_active' => true,
                'paid_amount' => $newMembership->price,
                'metadata' => array_merge($holder->metadata ?? [], [
                    'last_extension' => now(),
                    'correlation_id' => $correlationId,
                ]),
            ]);

            AuditLog::create([
                'tenant_id' => $user->tenant_id,
                'user_id' => $user->id,
                'model' => GymMembershipHolder::class,
                'model_id' => $holder->id,
                'action' => 'extended',
                'changes' => [
                    'membership_id' => $newMembership->id,
                    'expires_at' => $newExpiresAt,
                ],
                'correlation_id' => $correlationId,
            ]);

            Log::channel('sports')->info('Membership extended', [
                'holder_id' => $holder->id,
                'gym_id' => $holder->gym_id,
                'new_expires_at' => $newExpiresAt,
                'correlation_id' => $correlationId,
            ]);

            DB::commit();

            return [
                'success' => true,
                'new_expires_at' => $newExpiresAt,
                'correlation_id' => $correlationId,
            ];
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Failed to extend membership', [
                'error' => $e->getMessage(),
                'holder_id' => $holder->id,
            ]);
            throw $e;
        }
    }

    /**
     * Получить статистику посещаемости за период.
     * 
     * @param Gym $gym
     * @param \DateTime|null $from
     * @param \DateTime|null $to
     * @return array
     */
    public function getAttendanceStats(Gym $gym, ?\DateTime $from = null, ?\DateTime $to = null): array
    {
        try {
            $from = $from ?? now()->subMonth();
            $to = $to ?? now();

            // Общее количество посещений
            $totalVisits = GymAttendanceLog::where('gym_id', $gym->id)
                ->where('is_checkout', false)
                ->forPeriod($from, $to)
                ->count();

            // Уникальные посетители
            $uniqueVisitors = GymAttendanceLog::where('gym_id', $gym->id)
                ->where('is_checkout', false)
                ->forPeriod($from, $to)
                ->distinct('user_id')
                ->count();

            // Среднее посещение в день
            $daysInPeriod = $from->diffInDays($to) + 1;
            $avgVisitsPerDay = $daysInPeriod > 0 ? round($totalVisits / $daysInPeriod, 2) : 0;

            // Самые активные дни недели
            $activeByDayOfWeek = DB::table('gym_attendance_logs')
                ->select(DB::raw('DAYNAME(checked_at) as day, COUNT(*) as count'))
                ->where('gym_id', $gym->id)
                ->where('is_checkout', false)
                ->whereBetween('checked_at', [$from, $to])
                ->groupBy('day')
                ->orderByDesc('count')
                ->get();

            // Активные члены
            $activeMembers = GymMembershipHolder::where('gym_id', $gym->id)
                ->where('is_active', true)
                ->where('expires_at', '>', now())
                ->count();

            return [
                'period_from' => $from,
                'period_to' => $to,
                'total_visits' => $totalVisits,
                'unique_visitors' => $uniqueVisitors,
                'avg_visits_per_day' => $avgVisitsPerDay,
                'active_members' => $activeMembers,
                'by_day_of_week' => $activeByDayOfWeek->toArray(),
            ];
        } catch (Exception $e) {
            Log::error('Failed to get attendance stats', [
                'error' => $e->getMessage(),
                'gym_id' => $gym->id,
            ]);
            throw $e;
        }
    }

    /**
     * Получить финансовые метрики зала за период.
     * 
     * @param Gym $gym
     * @param \DateTime|null $from
     * @param \DateTime|null $to
     * @return array
     */
    public function getRevenueStats(Gym $gym, ?\DateTime $from = null, ?\DateTime $to = null): array
    {
        try {
            $from = $from ?? now()->subMonth();
            $to = $to ?? now();

            // Получить все членства зала
            $memberships = GymMembership::where('gym_id', $gym->id)->get();

            $totalRevenue = 0;
            $membershipStats = [];

            foreach ($memberships as $membership) {
                $revenue = $membership->revenueForPeriod($from, $to);
                $totalRevenue += $revenue;

                $membershipStats[] = [
                    'membership_id' => $membership->id,
                    'name' => $membership->name,
                    'revenue' => $revenue,
                    'active_holders' => $membership->activeHoldersCount(),
                ];
            }

            return [
                'period_from' => $from,
                'period_to' => $to,
                'total_revenue' => round($totalRevenue, 2),
                'avg_daily_revenue' => round($totalRevenue / max(1, $from->diffInDays($to)), 2),
                'memberships' => $membershipStats,
            ];
        } catch (Exception $e) {
            Log::error('Failed to get revenue stats', [
                'error' => $e->getMessage(),
                'gym_id' => $gym->id,
            ]);
            throw $e;
        }
    }
}
