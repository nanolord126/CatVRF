<?php

declare(strict_types=1);

namespace App\Domains\Staff\Services;

use App\Domains\Staff\Domain\Entities\Staff;
use App\Domains\Staff\Domain\Entities\StaffReview;
use App\Domains\Staff\Domain\Entities\StaffMentoring;
use App\Domains\Staff\Domain\Entities\StaffMentoringSession;
use App\Domains\Staff\Domain\Entities\StaffThank;
use App\Domains\Staff\Domain\Entities\StaffPost;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Carbon\Carbon;

/**
 * StaffSocialService — сервис социальных функций сотрудников.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Управляет peer reviews, наставничеством, социальной сетью, благодарностями.
 */
final class StaffSocialService
{
    use WithAuditLogging;

    public function __construct(
        private readonly FraudControlService $fraudControlService,
        private readonly AuditService $auditService,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Создаёт peer review.
     */
    public function createReview(array $data): StaffReview
    {
        // Fraud check
        $fraudResult = $this->fraudControlService->checkRequest([
            'action' => 'staff_review_create',
            'reviewer_id' => $data['reviewer_id'],
            'reviewee_id' => $data['reviewee_id'],
            'tenant_id' => $data['tenant_id'],
        ]);

        if (!$fraudResult->isAllowed()) {
            throw new \RuntimeException('Request blocked by fraud control');
        }

        // Проверяем, что reviewer != reviewee
        if ($data['reviewer_id'] === $data['reviewee_id']) {
            throw new \InvalidArgumentException('Reviewer cannot review themselves');
        }

        DB::transaction(function () use ($data) {
            $review = StaffReview::create([
                'tenant_id' => $data['tenant_id'],
                'reviewer_id' => $data['reviewer_id'],
                'reviewee_id' => $data['reviewee_id'],
                'overall_rating' => $data['overall_rating'],
                'communication_rating' => $data['communication_rating'] ?? null,
                'teamwork_rating' => $data['teamwork_rating'] ?? null,
                'leadership_rating' => $data['leadership_rating'] ?? null,
                'technical_rating' => $data['technical_rating'] ?? null,
                'strengths' => $data['strengths'] ?? [],
                'areas_for_improvement' => $data['areas_for_improvement'] ?? [],
                'comments' => $data['comments'] ?? null,
                'status' => 'pending',
                'is_anonymous' => $data['is_anonymous'] ?? false,
                'period' => $data['period'] ?? now()->format('Y-m'),
            ]);

            Cache::tags(['staff_social', 'staff:' . $data['reviewee_id']])->flush();

            $this->logCreated('staff_review', $review->id, [
                'reviewer_id' => $data['reviewer_id'],
                'reviewee_id' => $data['reviewee_id'],
                'overall_rating' => $data['overall_rating'],
            ]);

            return $review;
        });
    }

    /**
     * Одобряет peer review.
     */
    public function approveReview(int $reviewId, int $approvedBy): void
    {
        $review = StaffReview::findOrFail($reviewId);
        $review->update([
            'status' => 'approved',
        ]);

        Cache::tags(['staff_social', 'staff:' . $review->reviewee_id])->flush();

        $this->logAction('review_approved', [
            'entity_type' => 'staff_review',
            'entity_id' => $reviewId,
            'approved_by' => $approvedBy,
        ]);
    }

    /**
     * Создаёт отношение наставничества.
     */
    public function createMentoring(array $data): StaffMentoring
    {
        $fraudResult = $this->fraudControlService->checkRequest([
            'action' => 'staff_mentoring_create',
            'mentor_id' => $data['mentor_id'],
            'mentee_id' => $data['mentee_id'],
            'tenant_id' => $data['tenant_id'],
        ]);

        if (!$fraudResult->isAllowed()) {
            throw new \RuntimeException('Request blocked by fraud control');
        }

        if ($data['mentor_id'] === $data['mentee_id']) {
            throw new \InvalidArgumentException('Mentor cannot be the same as mentee');
        }

        $mentoring = StaffMentoring::create([
            'tenant_id' => $data['tenant_id'],
            'mentor_id' => $data['mentor_id'],
            'mentee_id' => $data['mentee_id'],
            'goals' => $data['goals'] ?? [],
            'focus_area' => $data['focus_area'] ?? null,
            'start_date' => $data['start_date'] ?? now(),
            'end_date' => $data['end_date'] ?? now()->addMonths(6),
            'status' => 'active',
            'status_notes' => null,
            'sessions_count' => $data['sessions_count'] ?? 10,
            'sessions_completed' => 0,
            'effectiveness_rating' => null,
        ]);

        Cache::tags(['staff_social', 'staff:' . $data['mentee_id']])->flush();

        $this->logCreated('staff_mentoring', $mentoring->id, [
            'mentor_id' => $data['mentor_id'],
            'mentee_id' => $data['mentee_id'],
        ]);

        return $mentoring;
    }

    /**
     * Записывает сессию наставничества.
     */
    public function createMentoringSession(int $mentoringId, array $data): StaffMentoringSession
    {
        $mentoring = StaffMentoring::findOrFail($mentoringId);

        $session = StaffMentoringSession::create([
            'tenant_id' => $mentoring->tenant_id,
            'mentoring_id' => $mentoringId,
            'scheduled_at' => $data['scheduled_at'],
            'completed_at' => $data['completed_at'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? 60,
            'topics' => $data['topics'] ?? [],
            'notes' => $data['notes'] ?? null,
            'action_items' => $data['action_items'] ?? [],
            'status' => $data['completed_at'] ? 'completed' : 'scheduled',
        ]);

        if ($data['completed_at'] ?? false) {
            $mentoring->increment('sessions_completed');
        }

        Cache::tags(['staff_social', 'staff:' . $mentoring->mentee_id])->flush();

        $this->logAction('mentoring_session_created', [
            'entity_type' => 'staff_mentoring_session',
            'entity_id' => $session->id,
            'mentoring_id' => $mentoringId,
        ]);

        return $session;
    }

    /**
     * Отправляет благодарность.
     */
    public function sendThank(array $data): StaffThank
    {
        $fraudResult = $this->fraudControlService->checkRequest([
            'action' => 'staff_thank_send',
            'sender_id' => $data['sender_id'],
            'receiver_id' => $data['receiver_id'],
            'tenant_id' => $data['tenant_id'],
        ]);

        if (!$fraudResult->isAllowed()) {
            throw new \RuntimeException('Request blocked by fraud control');
        }

        if ($data['sender_id'] === $data['receiver_id']) {
            throw new \InvalidArgumentException('Sender cannot thank themselves');
        }

        $thank = StaffThank::create([
            'tenant_id' => $data['tenant_id'],
            'sender_id' => $data['sender_id'],
            'receiver_id' => $data['receiver_id'],
            'type' => $data['type'] ?? 'thanks',
            'message' => $data['message'],
            'badge_type' => $data['badge_type'] ?? null,
            'is_public' => $data['is_public'] ?? true,
            'related_entity_type' => $data['related_entity_type'] ?? null,
            'related_entity_id' => $data['related_entity_id'] ?? null,
        ]);

        Cache::tags(['staff_social', 'staff:' . $data['receiver_id']])->flush();

        // Начисляем очки за благодарность (если публичная)
        if ($thank->is_public) {
            app(StaffGamificationService::class)->awardPoints(
                $data['receiver_id'],
                10,
                'thank_received',
                "Received thanks from staff",
            );
        }

        $this->logCreated('staff_thank', $thank->id, [
            'sender_id' => $data['sender_id'],
            'receiver_id' => $data['receiver_id'],
            'type' => $data['type'],
        ]);

        return $thank;
    }

    /**
     * Создаёт пост в социальной сети.
     */
    public function createPost(array $data): StaffPost
    {
        $fraudResult = $this->fraudControlService->checkRequest([
            'action' => 'staff_post_create',
            'author_id' => $data['author_id'],
            'tenant_id' => $data['tenant_id'],
        ]);

        if (!$fraudResult->isAllowed()) {
            throw new \RuntimeException('Request blocked by fraud control');
        }

        $post = StaffPost::create([
            'tenant_id' => $data['tenant_id'],
            'author_id' => $data['author_id'],
            'content' => $data['content'],
            'type' => $data['type'] ?? 'post',
            'attachments' => $data['attachments'] ?? [],
            'likes_count' => 0,
            'comments_count' => 0,
            'shares_count' => 0,
            'is_pinned' => $data['is_pinned'] ?? false,
            'allow_comments' => $data['allow_comments'] ?? true,
        ]);

        Cache::tags(['staff_social_posts'])->flush();

        $this->logCreated('staff_post', $post->id, [
            'author_id' => $data['author_id'],
            'type' => $data['type'],
        ]);

        return $post;
    }

    /**
     * Ставит лайк на пост или комментарий.
     */
    public function toggleLike(int $staffId, string $likeableType, int $likeableId): bool
    {
        $existing = \App\Domains\Staff\Domain\Entities\StaffLike::where('staff_id', $staffId)
            ->where('likeable_type', $likeableType)
            ->where('likeable_id', $likeableId)
            ->first();

        if ($existing) {
            $existing->delete();
            $this->decrementLikeCount($likeableType, $likeableId);
            return false;
        } else {
            \App\Domains\Staff\Domain\Entities\StaffLike::create([
                'tenant_id' => Staff::findOrFail($staffId)->tenant_id,
                'staff_id' => $staffId,
                'likeable_type' => $likeableType,
                'likeable_id' => $likeableId,
            ]);
            $this->incrementLikeCount($likeableType, $likeableId);
            return true;
        }
    }

    /**
     * Получает peer reviews для сотрудника.
     */
    public function getReviewsForStaff(int $staffId, ?string $period = null): array
    {
        $cacheKey = "staff_reviews:{$staffId}:" . ($period ?? 'all');

        return Cache::tags(['staff_social', 'staff:' . $staffId])->remember(
            $cacheKey,
            now()->addHours(6),
            function () use ($staffId, $period) {
                $query = StaffReview::where('reviewee_id', $staffId)
                    ->approved()
                    ->with('reviewer');

                if ($period) {
                    $query->where('period', $period);
                }

                return $query->latest()
                    ->get()
                    ->map(fn ($r) => [
                        'id' => $r->id,
                        'reviewer_name' => $r->is_anonymous ? 'Anonymous' : ($r->reviewer->full_name ?? 'Unknown'),
                        'overall_rating' => $r->overall_rating,
                        'average_rating' => $r->average_rating,
                        'strengths' => $r->strengths ?? [],
                        'areas_for_improvement' => $r->areas_for_improvement ?? [],
                        'comments' => $r->comments,
                        'period' => $r->period,
                    ])
                    ->toArray();
            },
        );
    }

    /**
     * Получает активные отношения наставничества.
     */
    public function getActiveMentoring(int $tenantId): array
    {
        $cacheKey = "active_mentoring:{$tenantId}";

        return Cache::tags(['staff_social'])->remember(
            $cacheKey,
            now()->addHours(12),
            function () use ($tenantId) {
                return StaffMentoring::where('tenant_id', $tenantId)
                    ->active()
                    ->with(['mentor', 'mentee'])
                    ->get()
                    ->map(fn ($m) => [
                        'id' => $m->id,
                        'mentor_name' => $m->mentor->full_name ?? 'Unknown',
                        'mentee_name' => $m->mentee->full_name ?? 'Unknown',
                        'focus_area' => $m->focus_area,
                        'progress' => $m->progress,
                        'start_date' => $m->start_date->toDateString(),
                        'end_date' => $m->end_date->toDateString(),
                    ])
                    ->toArray();
            },
        );
    }

    /**
     * Получает ленту постов.
     */
    public function getFeed(int $tenantId, int $limit = 20): array
    {
        $cacheKey = "staff_feed:{$tenantId}";

        return Cache::tags(['staff_social_posts'])->remember(
            $cacheKey,
            now()->addMinutes(15),
            function () use ($tenantId, $limit) {
                return StaffPost::where('tenant_id', $tenantId)
                    ->with('author')
                    ->latest()
                    ->limit($limit)
                    ->get()
                    ->map(fn ($p) => [
                        'id' => $p->id,
                        'author_name' => $p->author->full_name ?? 'Unknown',
                        'content' => $p->content,
                        'type' => $p->type,
                        'likes_count' => $p->likes_count,
                        'comments_count' => $p->comments_count,
                        'created_at' => $p->created_at->toIso8601String(),
                        'is_pinned' => $p->is_pinned,
                    ])
                    ->toArray();
            },
        );
    }

    private function incrementLikeCount(string $type, int $id): void
    {
        if ($type === StaffPost::class) {
            StaffPost::where('id', $id)->increment('likes_count');
        }
    }

    private function decrementLikeCount(string $type, int $id): void
    {
        if ($type === StaffPost::class) {
            StaffPost::where('id', $id)->decrement('likes_count');
        }
    }
}
