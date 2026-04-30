<?php

declare(strict_types=1);

namespace App\Domains\Staff\Services;

use App\Domains\Staff\Domain\Entities\Staff;
use App\Domains\Staff\Domain\Entities\StaffBadge;
use App\Domains\Staff\Domain\Entities\StaffAchievement;
use App\Domains\Staff\Domain\Entities\StaffChallenge;
use App\Domains\Staff\Domain\Entities\StaffChallengeParticipant;
use App\Domains\Staff\Domain\Entities\StaffPoint;
use App\Domains\Staff\Domain\Entities\StaffPointHistory;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Carbon\Carbon;

/**
 * StaffGamificationService — сервис геймификации сотрудников.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Управляет бейджами, достижениями, челленджами, очками, уровнями.
 */
final class StaffGamificationService
{
    use WithAuditLogging;

    public function __construct(
        private readonly FraudControlService $fraudControlService,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Начисляет очки сотруднику.
     */
    public function awardPoints(int $staffId, int $points, string $source, ?string $description = null): StaffPoint
    {
        $fraudResult = $this->fraudControlService->checkRequest([
            'action' => 'staff_points_award',
            'staff_id' => $staffId,
            'tenant_id' => auth()->user()?->tenant_id ?? null,
        ]);

        if (!$fraudResult->isAllowed()) {
            throw new \RuntimeException('Request blocked by fraud control');
        }

        $staff = Staff::findOrFail($staffId);

        DB::transaction(function () use ($staff, $points, $source, $description) {
            $pointRecord = StaffPoint::firstOrCreate(
                ['staff_id' => $staffId],
                [
                    'tenant_id' => $staff->tenant_id,
                    'total_points' => 0,
                    'available_points' => 0,
                    'spent_points' => 0,
                    'level' => 1,
                    'xp' => 0,
                    'xp_to_next_level' => 100,
                    'challenges_completed' => 0,
                    'badges_earned' => 0,
                ]
            );

            $pointRecord->increment('total_points', $points);
            $pointRecord->increment('available_points', $points);
            $pointRecord->increment('xp', $points);

            $this->checkLevelUp($pointRecord);

            StaffPointHistory::create([
                'tenant_id' => $staff->tenant_id,
                'staff_id' => $staffId,
                'points_change' => $points,
                'type' => 'earned',
                'source' => $source,
                'description' => $description,
                'balance_after' => $pointRecord->total_points,
            ]);

            Cache::tags(['staff_gamification', 'staff:' . $staffId])->flush();

            $this->logAction('points_awarded', [
                'entity_type' => 'staff_point',
                'entity_id' => $pointRecord->id,
                'staff_id' => $staffId,
                'points' => $points,
                'source' => $source,
            ]);

            return $pointRecord;
        });
    }

    /**
     * Списывает очки у сотрудника.
     */
    public function deductPoints(int $staffId, int $points, string $source, ?string $description = null): bool
    {
        $fraudResult = $this->fraudControlService->checkRequest([
            'action' => 'staff_points_deduct',
            'staff_id' => $staffId,
            'tenant_id' => auth()->user()?->tenant_id ?? null,
        ]);

        if (!$fraudResult->isAllowed()) {
            throw new \RuntimeException('Request blocked by fraud control');
        }

        $pointRecord = StaffPoint::where('staff_id', $staffId)->first();

        if (!$pointRecord || $pointRecord->available_points < $points) {
            return false;
        }

        DB::transaction(function () use ($pointRecord, $points, $source, $description) {
            $pointRecord->decrement('available_points', $points);
            $pointRecord->increment('spent_points', $points);

            StaffPointHistory::create([
                'tenant_id' => $pointRecord->tenant_id,
                'staff_id' => $pointRecord->staff_id,
                'points_change' => -$points,
                'type' => 'spent',
                'source' => $source,
                'description' => $description,
                'balance_after' => $pointRecord->total_points,
            ]);

            Cache::tags(['staff_gamification', 'staff:' . $pointRecord->staff_id])->flush();

            $this->logAction('points_deducted', [
                'entity_type' => 'staff_point',
                'entity_id' => $pointRecord->id,
                'staff_id' => $pointRecord->staff_id,
                'points' => $points,
                'source' => $source,
            ]);
        });

        return true;
    }

    /**
     * Выдаёт бейдж сотруднику.
     */
    public function awardBadge(int $staffId, int $badgeId, ?array $context = null): StaffAchievement
    {
        $fraudResult = $this->fraudControlService->checkRequest([
            'action' => 'staff_badge_award',
            'staff_id' => $staffId,
            'tenant_id' => auth()->user()?->tenant_id ?? null,
        ]);

        if (!$fraudResult->isAllowed()) {
            throw new \RuntimeException('Request blocked by fraud control');
        }

        $staff = Staff::findOrFail($staffId);
        $badge = StaffBadge::findOrFail($badgeId);

        // Проверяем, не выдан ли уже бейдж
        $existing = StaffAchievement::where('staff_id', $staffId)
            ->where('badge_id', $badgeId)
            ->first();

        if ($existing) {
            return $existing;
        }

        DB::transaction(function () use ($staff, $badge, $context) {
            $achievement = StaffAchievement::create([
                'tenant_id' => $staff->tenant_id,
                'staff_id' => $staffId,
                'badge_id' => $badgeId,
                'earned_at' => now(),
                'context' => $context,
            ]);

            $badge->increment('total_earned');

            // Начисляем очки за бейдж
            if ($badge->points_reward > 0) {
                $this->awardPoints(
                    $staffId,
                    $badge->points_reward,
                    'badge_earned',
                    "Earned badge: {$badge->name}",
                );
            }

            // Обновляем счётчик бейджей
            StaffPoint::where('staff_id', $staffId)->increment('badges_earned');

            Cache::tags(['staff_gamification', 'staff:' . $staffId])->flush();

            $this->logAction('badge_awarded', [
                'entity_type' => 'staff_achievement',
                'entity_id' => $achievement->id,
                'staff_id' => $staffId,
                'badge_id' => $badgeId,
            ]);

            return $achievement;
        });
    }

    /**
     * Создаёт челлендж.
     */
    public function createChallenge(array $data): StaffChallenge
    {
        $fraudResult = $this->fraudControlService->checkRequest([
            'action' => 'staff_challenge_create',
            'tenant_id' => $data['tenant_id'],
        ]);

        if (!$fraudResult->isAllowed()) {
            throw new \RuntimeException('Request blocked by fraud control');
        }

        $challenge = StaffChallenge::create([
            'tenant_id' => $data['tenant_id'],
            'title' => $data['title'],
            'description' => $data['description'],
            'type' => $data['type'],
            'category' => $data['category'],
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'requirements' => $data['requirements'],
            'points_reward' => $data['points_reward'] ?? 0,
            'badge_id' => $data['badge_id'] ?? null,
            'status' => 'active',
            'participants_count' => 0,
            'completions_count' => 0,
        ]);

        Cache::tags(['staff_challenges'])->flush();

        $this->logCreated('staff_challenge', $challenge->id, [
            'title' => $challenge->title,
            'type' => $challenge->type,
        ]);

        return $challenge;
    }

    /**
     * Регистрирует сотрудника в челлендже.
     */
    public function joinChallenge(int $staffId, int $challengeId): StaffChallengeParticipant
    {
        $fraudResult = $this->fraudControlService->checkRequest([
            'action' => 'staff_challenge_join',
            'staff_id' => $staffId,
            'tenant_id' => auth()->user()?->tenant_id ?? null,
        ]);

        if (!$fraudResult->isAllowed()) {
            throw new \RuntimeException('Request blocked by fraud control');
        }

        $staff = Staff::findOrFail($staffId);
        $challenge = StaffChallenge::findOrFail($challengeId);

        $participant = StaffChallengeParticipant::firstOrCreate(
            [
                'staff_id' => $staffId,
                'challenge_id' => $challengeId,
            ],
            [
                'tenant_id' => $staff->tenant_id,
                'progress' => 0,
                'progress_data' => [],
                'status' => 'in_progress',
                'completed_at' => null,
                'rank' => null,
                'points_earned' => 0,
            ]
        );

        if ($participant->wasRecentlyCreated) {
            $challenge->increment('participants_count');
            Cache::tags(['staff_challenges', 'staff:' . $staffId])->flush();

            $this->logAction('challenge_joined', [
                'entity_type' => 'staff_challenge_participant',
                'entity_id' => $participant->id,
                'staff_id' => $staffId,
                'challenge_id' => $challengeId,
            ]);
        }

        return $participant;
    }

    /**
     * Обновляет прогресс в челлендже.
     */
    public function updateChallengeProgress(int $participantId, float $progress, array $progressData): void
    {
        $participant = StaffChallengeParticipant::findOrFail($participantId);

        $participant->update([
            'progress' => min(100, $progress),
            'progress_data' => $progressData,
        ]);

        if ($progress >= 100 && $participant->status !== 'completed') {
            $this->completeChallenge($participantId);
        }

        Cache::tags(['staff_challenges', 'staff:' . $participant->staff_id])->flush();
    }

    /**
     * Завершает челлендж для участника.
     */
    public function completeChallenge(int $participantId): StaffChallengeParticipant
    {
        $participant = StaffChallengeParticipant::findOrFail($participantId);
        $challenge = $participant->challenge;

        if ($participant->status === 'completed') {
            return $participant;
        }

        DB::transaction(function () use ($participant, $challenge) {
            $participant->update([
                'status' => 'completed',
                'completed_at' => now(),
                'rank' => $challenge->completions_count + 1,
            ]);

            $challenge->increment('completions_count');

            // Начисляем очки
            if ($challenge->points_reward > 0) {
                $this->awardPoints(
                    $participant->staff_id,
                    $challenge->points_reward,
                    'challenge_completed',
                    "Completed challenge: {$challenge->title}",
                );
            }

            // Выдаём бейдж если есть
            if ($challenge->badge_id) {
                $this->awardBadge($participant->staff_id, $challenge->badge_id);
            }

            // Обновляем счётчик челленджей
            StaffPoint::where('staff_id', $participant->staff_id)->increment('challenges_completed');

            Cache::tags(['staff_challenges', 'staff:' . $participant->staff_id])->flush();

            $this->logAction('challenge_completed', [
                'entity_type' => 'staff_challenge_participant',
                'entity_id' => $participant->id,
                'staff_id' => $participant->staff_id,
                'challenge_id' => $challenge->id,
                'rank' => $participant->rank,
            ]);
        });

        return $participant->fresh();
    }

    /**
     * Получает лидерборд по очкам.
     */
    public function getLeaderboard(int $tenantId, int $limit = 10): array
    {
        $cacheKey = "leaderboard:{$tenantId}";

        return Cache::tags(['staff_gamification'])->remember(
            $cacheKey,
            now()->addHours(6),
            function () use ($tenantId, $limit) {
                return StaffPoint::where('tenant_id', $tenantId)
                    ->with('staff')
                    ->orderByDesc('total_points')
                    ->limit($limit)
                    ->get()
                    ->map(fn ($p, $index) => [
                        'rank' => $index + 1,
                        'staff_id' => $p->staff_id,
                        'staff_name' => $p->staff->full_name ?? 'Unknown',
                        'total_points' => $p->total_points,
                        'level' => $p->level,
                        'badges_earned' => $p->badges_earned,
                        'challenges_completed' => $p->challenges_completed,
                    ])
                    ->toArray();
            },
        );
    }

    /**
     * Получает лидерборд по уровню.
     */
    public function getLevelLeaderboard(int $tenantId, int $limit = 10): array
    {
        $cacheKey = "level_leaderboard:{$tenantId}";

        return Cache::tags(['staff_gamification'])->remember(
            $cacheKey,
            now()->addHours(6),
            function () use ($tenantId, $limit) {
                return StaffPoint::where('tenant_id', $tenantId)
                    ->with('staff')
                    ->orderByDesc('level')
                    ->orderByDesc('xp')
                    ->limit($limit)
                    ->get()
                    ->map(fn ($p, $index) => [
                        'rank' => $index + 1,
                        'staff_id' => $p->staff_id,
                        'staff_name' => $p->staff->full_name ?? 'Unknown',
                        'level' => $p->level,
                        'xp' => $p->xp,
                        'level_title' => $p->level_title,
                    ])
                    ->toArray();
            },
        );
    }

    /**
     * Проверяет и повышает уровень.
     */
    private function checkLevelUp(StaffPoint $pointRecord): void
    {
        while ($pointRecord->xp >= $pointRecord->xp_to_next_level) {
            $pointRecord->xp -= $pointRecord->xp_to_next_level;
            $pointRecord->level++;
            $pointRecord->xp_to_next_level = $this->calculateXpForNextLevel($pointRecord->level);
        }

        $pointRecord->save();
    }

    /**
     * Рассчитывает XP для следующего уровня.
     */
    private function calculateXpForNextLevel(int $level): int
    {
        return (int) (100 * pow(1.2, $level - 1));
    }

    /**
     * Получает статистику геймификации сотрудника.
     */
    public function getGamificationStats(int $staffId): array
    {
        $pointRecord = StaffPoint::where('staff_id', $staffId)->first();

        if (!$pointRecord) {
            return [
                'total_points' => 0,
                'available_points' => 0,
                'level' => 1,
                'xp' => 0,
                'xp_progress' => 0,
                'badges_earned' => 0,
                'challenges_completed' => 0,
                'achievements' => [],
            ];
        }

        $achievements = StaffAchievement::where('staff_id', $staffId)
            ->with('badge')
            ->latest()
            ->limit(10)
            ->get();

        return [
            'total_points' => $pointRecord->total_points,
            'available_points' => $pointRecord->available_points,
            'spent_points' => $pointRecord->spent_points,
            'level' => $pointRecord->level,
            'level_title' => $pointRecord->level_title,
            'xp' => $pointRecord->xp,
            'xp_to_next_level' => $pointRecord->xp_to_next_level,
            'xp_progress' => $pointRecord->xp_progress,
            'badges_earned' => $pointRecord->badges_earned,
            'challenges_completed' => $pointRecord->challenges_completed,
            'recent_achievements' => $achievements->map(fn ($a) => [
                'badge_name' => $a->badge->name,
                'earned_at' => $a->earned_at->toDateString(),
            ])->toArray(),
        ];
    }
}
