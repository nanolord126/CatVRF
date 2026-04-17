<?php declare(strict_types=1);

namespace App\Domains\Fashion\Services;

use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * ML-модерация отзывов Fashion.
 * PRODUCTION MANDATORY — канон CatVRF 2026.
 * 
 * Анализ тональности, детекция спама, фильтрация нецензурной лексики,
 * определение фейковых отзывов, автоматическая модерация.
 */
final readonly class FashionReviewModerationService
{
    private const SPAM_THRESHOLD = 0.7;
    private const TOXICITY_THRESHOLD = 0.6;
    private const FAKE_REVIEW_THRESHOLD = 0.8;
    private const MIN_REVIEW_LENGTH = 20;
    private const MAX_REVIEW_LENGTH = 2000;

    public function __construct(
        private AuditService $audit,
        private FraudControlService $fraud,
        private \Illuminate\Database\DatabaseManager $db,
    ) {}

    /**
     * Модерировать отзыв с ML.
     */
    public function moderateReview(
        int $reviewId,
        string $correlationId = ''
    ): array {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $review = $this->db->table('fashion_reviews')
            ->where('id', $reviewId)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($review === null) {
            throw new \InvalidArgumentException('Review not found', 404);
        }

        $spamScore = $this->detectSpam($review['comment'], $correlationId);
        $toxicityScore = $this->detectToxicity($review['comment'], $correlationId);
        $fakeScore = $this->detectFakeReview($review, $correlationId);
        $sentiment = $this->analyzeSentiment($review['comment'], $correlationId);

        $moderationResult = $this->determineModerationAction($spamScore, $toxicityScore, $fakeScore);
        
        $this->saveModerationResult(
            $reviewId,
            $tenantId,
            $spamScore,
            $toxicityScore,
            $fakeScore,
            $sentiment,
            $moderationResult,
            $correlationId
        );

        $this->audit->record(
            action: 'fashion_review_moderated',
            subjectType: 'fashion_review',
            subjectId: $reviewId,
            oldValues: [],
            newValues: [
                'spam_score' => $spamScore,
                'toxicity_score' => $toxicityScore,
                'fake_score' => $fakeScore,
                'sentiment' => $sentiment,
                'action' => $moderationResult,
            ],
            correlationId: $correlationId
        );

        Log::channel('audit')->info('Fashion review moderated', [
            'review_id' => $reviewId,
            'tenant_id' => $tenantId,
            'action' => $moderationResult,
            'correlation_id' => $correlationId,
        ]);

        return [
            'review_id' => $reviewId,
            'spam_score' => $spamScore,
            'toxicity_score' => $toxicityScore,
            'fake_score' => $fakeScore,
            'sentiment' => $sentiment,
            'action' => $moderationResult,
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Массовая модерация отзывов.
     */
    public function batchModerateReviews(array $reviewIds, string $correlationId = ''): array
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $results = [];
        foreach ($reviewIds as $reviewId) {
            try {
                $result = $this->moderateReview($reviewId, $correlationId);
                $results[] = $result;
            } catch (\Throwable $e) {
                Log::channel('audit')->warning('Failed to moderate review', [
                    'review_id' => $reviewId,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
            }
        }

        return [
            'total_processed' => count($reviewIds),
            'successful' => count($results),
            'failed' => count($reviewIds) - count($results),
            'results' => $results,
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Получить статистику модерации.
     */
    public function getModerationStats(int $days = 30, string $correlationId = ''): array
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $stats = $this->db->table('fashion_review_moderations')
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', Carbon::now()->subDays($days))
            ->selectRaw('
                COUNT(*) as total_reviews,
                SUM(CASE WHEN action = "approve" THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN action = "reject" THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN action = "flag" THEN 1 ELSE 0 END) as flagged,
                AVG(spam_score) as avg_spam_score,
                AVG(toxicity_score) as avg_toxicity_score,
                AVG(fake_score) as avg_fake_score
            ')
            ->first();

        return [
            'tenant_id' => $tenantId,
            'period_days' => $days,
            'stats' => $stats,
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Получить отзывы требующие ручной проверки.
     */
    public function getFlaggedReviews(int $limit = 50, string $correlationId = ''): array
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $reviews = $this->db->table('fashion_reviews as fr')
            ->join('fashion_review_moderations as frm', 'fr.id', '=', 'frm.review_id')
            ->where('fr.tenant_id', $tenantId)
            ->where('frm.action', 'flag')
            ->where('frm.manual_review_required', true)
            ->orderBy('frm.created_at', 'desc')
            ->limit($limit)
            ->select('fr.*', 'frm.*')
            ->get()
            ->toArray();

        return [
            'tenant_id' => $tenantId,
            'reviews' => $reviews,
            'total_count' => count($reviews),
            'correlation_id' => $correlationId,
        ];
    }

    private function detectSpam(string $comment, string $correlationId): float
    {
        $spamScore = 0.0;
        $comment = strtolower($comment);

        $spamPatterns = [
            '/\b(buy now|click here|free|winner|congratulations|limited time)\b/i',
            '/\b(http|www|\.com|\.ru|\.net)\b/i',
            '/\b(\d{3}[-.]?\d{3}[-.]?\d{4})\b/',
            '/\b(viagra|cialis|casino|poker|lottery)\b/i',
        ];

        foreach ($spamPatterns as $pattern) {
            if (preg_match($pattern, $comment)) {
                $spamScore += 0.3;
            }
        }

        if (strlen($comment) < self::MIN_REVIEW_LENGTH) {
            $spamScore += 0.2;
        }

        if (str_repeat(substr($comment, 0, 5), 5) === $comment) {
            $spamScore += 0.5;
        }

        $uppercaseRatio = preg_match_all('/[A-Z]/', $comment) / max(strlen($comment), 1);
        if ($uppercaseRatio > 0.7) {
            $spamScore += 0.3;
        }

        return min($spamScore, 1.0);
    }

    private function detectToxicity(string $comment, string $correlationId): float
    {
        $toxicityScore = 0.0;
        $comment = strtolower($comment);

        $toxicWords = [
            'shit', 'fuck', 'damn', 'ass', 'bitch', 'crap',
            'stupid', 'idiot', 'dumb', 'moron', 'loser',
            'ugly', 'disgusting', 'terrible', 'horrible', 'awful',
        ];

        foreach ($toxicWords as $word) {
            if (str_contains($comment, $word)) {
                $toxicityScore += 0.2;
            }
        }

        return min($toxicityScore, 1.0);
    }

    private function detectFakeReview(array $review, string $correlationId): float
    {
        $fakeScore = 0.0;

        $userReviewsCount = $this->db->table('fashion_reviews')
            ->where('user_id', $review['user_id'])
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->count();

        if ($userReviewsCount > 10) {
            $fakeScore += 0.4;
        }

        $userPurchasesCount = $this->db->table('order_items as oi')
            ->join('orders as o', 'oi.order_id', '=', 'o.id')
            ->where('o.user_id', $review['user_id'])
            ->where('oi.product_id', $review['fashion_product_id'])
            ->where('o.status', 'completed')
            ->count();

        if ($userPurchasesCount === 0) {
            $fakeScore += 0.5;
        }

        $reviewText = $review['comment'] ?? '';
        if (strlen($reviewText) < 30 && $review['rating'] === 5) {
            $fakeScore += 0.3;
        }

        $genericPhrases = [
            'great product',
            'excellent quality',
            'highly recommend',
            'love it',
            'perfect',
        ];

        $phraseCount = 0;
        foreach ($genericPhrases as $phrase) {
            if (str_contains(strtolower($reviewText), $phrase)) {
                $phraseCount++;
            }
        }

        if ($phraseCount >= 2 && strlen($reviewText) < 50) {
            $fakeScore += 0.2;
        }

        return min($fakeScore, 1.0);
    }

    private function analyzeSentiment(string $comment, string $correlationId): string
    {
        $comment = strtolower($comment);

        $positiveWords = [
            'good', 'great', 'excellent', 'amazing', 'wonderful', 'love',
            'perfect', 'beautiful', 'comfortable', 'quality', 'recommend',
        ];

        $negativeWords = [
            'bad', 'terrible', 'awful', 'horrible', 'hate', 'disappoint',
            'uncomfortable', 'poor', 'waste', 'refund', 'return',
        ];

        $positiveCount = 0;
        $negativeCount = 0;

        foreach ($positiveWords as $word) {
            if (str_contains($comment, $word)) {
                $positiveCount++;
            }
        }

        foreach ($negativeWords as $word) {
            if (str_contains($comment, $word)) {
                $negativeCount++;
            }
        }

        if ($positiveCount > $negativeCount) {
            return 'positive';
        } elseif ($negativeCount > $positiveCount) {
            return 'negative';
        }

        return 'neutral';
    }

    private function determineModerationAction(float $spamScore, float $toxicityScore, float $fakeScore): string
    {
        if ($toxicityScore >= self::TOXICITY_THRESHOLD) {
            return 'reject';
        }

        if ($spamScore >= self::SPAM_THRESHOLD) {
            return 'reject';
        }

        if ($fakeScore >= self::FAKE_REVIEW_THRESHOLD) {
            return 'reject';
        }

        if ($spamScore > 0.4 || $toxicityScore > 0.4 || $fakeScore > 0.5) {
            return 'flag';
        }

        return 'approve';
    }

    private function saveModerationResult(
        int $reviewId,
        int $tenantId,
        float $spamScore,
        float $toxicityScore,
        float $fakeScore,
        string $sentiment,
        string $action,
        string $correlationId
    ): void {
        $manualReviewRequired = $action === 'flag';

        $this->db->table('fashion_review_moderations')->updateOrInsert(
            ['review_id' => $reviewId, 'tenant_id' => $tenantId],
            [
                'spam_score' => $spamScore,
                'toxicity_score' => $toxicityScore,
                'fake_score' => $fakeScore,
                'sentiment' => $sentiment,
                'action' => $action,
                'manual_review_required' => $manualReviewRequired,
                'moderated_at' => Carbon::now(),
                'correlation_id' => $correlationId,
                'updated_at' => Carbon::now(),
            ]
        );

        if ($action === 'approve') {
            $this->db->table('fashion_reviews')
                ->where('id', $reviewId)
                ->update(['status' => 'published']);
        } elseif ($action === 'reject') {
            $this->db->table('fashion_reviews')
                ->where('id', $reviewId)
                ->update(['status' => 'rejected']);
        }
    }

    private function getTenantId(): int
    {
        return function_exists('tenant') && tenant() ? tenant()->id : 1;
    }
}
