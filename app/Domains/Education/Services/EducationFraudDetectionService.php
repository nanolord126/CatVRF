<?php declare(strict_types=1);

namespace App\Domains\Education\Services;

use App\Domains\Education\DTOs\CheatingDetectionDto;
use App\Domains\Education\DTOs\ReviewFraudDto;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

final readonly class EducationFraudDetectionService
{
    private const CHEATING_THRESHOLD = 0.75;
    private const FRAUD_THRESHOLD = 0.8;
    private const CACHE_TTL = 3600;

    public function __construct(
        private AuditService $audit,
    ) {}

    public function detectCheating(int $enrollmentId, int $userId, string $correlationId): CheatingDetectionDto
    {
        $enrollment = DB::table('enrollments')->where('id', $enrollmentId)->first();

        if ($enrollment === null) {
            throw new \DomainException('Enrollment not found');
        }

        $cacheKey = "education:fraud:cheating:enrollment:{$enrollmentId}";
        $cached = Redis::get($cacheKey);

        if ($cached !== null) {
            return CheatingDetectionDto::fromArray(json_decode($cached, true));
        }

        $riskFactors = $this->analyzeCheatingRiskFactors($enrollmentId, $userId);
        $cheatingProbability = $this->calculateCheatingProbability($riskFactors);
        $isCheating = $cheatingProbability >= self::CHEATING_THRESHOLD;
        $severity = $this->determineSeverity($cheatingProbability);

        $detection = new CheatingDetectionDto(
            detectionId: (string) Str::uuid(),
            userId: $userId,
            enrollmentId: $enrollmentId,
            tenantId: $enrollment->tenant_id,
            businessGroupId: $enrollment->business_group_id,
            isCheating: $isCheating,
            cheatingProbability: $cheatingProbability,
            riskFactors: $riskFactors,
            severity: $severity,
            correlationId: $correlationId,
            detectedAt: now()->toIso8601String(),
        );

        Redis::setex($cacheKey, self::CACHE_TTL, json_encode($detection->toArray()));

        if ($isCheating) {
            $this->handleCheatingDetection($detection);
        }

        return $detection;
    }

    public function detectReviewFraud(int $reviewId, int $userId, string $reviewContent, string $correlationId): ReviewFraudDto
    {
        $review = DB::table('education_course_reviews')->where('id', $reviewId)->first();

        if ($review === null) {
            throw new \DomainException('Review not found');
        }

        $cacheKey = "education:fraud:review:{$reviewId}";
        $cached = Redis::get($cacheKey);

        if ($cached !== null) {
            return ReviewFraudDto::fromArray(json_decode($cached, true));
        }

        $fraudIndicators = $this->analyzeReviewFraudIndicators($reviewId, $userId, $reviewContent);
        $fraudProbability = $this->calculateFraudProbability($fraudIndicators);
        $isFraudulent = $fraudProbability >= self::FRAUD_THRESHOLD;
        $severity = $this->determineSeverity($fraudProbability);

        $detection = new ReviewFraudDto(
            detectionId: (string) Str::uuid(),
            reviewId: $reviewId,
            userId: $userId,
            tenantId: $review->tenant_id,
            isFraudulent: $isFraudulent,
            fraudProbability: $fraudProbability,
            fraudIndicators: $fraudIndicators,
            severity: $severity,
            correlationId: $correlationId,
            detectedAt: now()->toIso8601String(),
        );

        Redis::setex($cacheKey, self::CACHE_TTL, json_encode($detection->toArray()));

        if ($isFraudulent) {
            $this->handleReviewFraudDetection($detection);
        }

        return $detection;
    }

    private function analyzeCheatingRiskFactors(int $enrollmentId, int $userId): array
    {
        $factors = [];

        $activityPattern = $this->analyzeActivityPattern($enrollmentId, $userId);
        $factors['abnormal_activity'] = $activityPattern['is_abnormal'];
        $factors['activity_score'] = $activityPattern['score'];

        $timeSpent = $this->analyzeTimeSpent($enrollmentId);
        $factors['suspicious_time_spent'] = $timeSpent['is_suspicious'];
        $factors['time_score'] = $timeSpent['score'];

        $answerPattern = $this->analyzeAnswerPattern($enrollmentId);
        $factors['similar_answers'] = $answerPattern['has_similar_answers'];
        $factors['answer_score'] = $answerPattern['score'];

        $ipChanges = $this->analyzeIpChanges($enrollmentId, $userId);
        $factors['multiple_ips'] = $ipChanges['has_multiple_ips'];
        $factors['ip_score'] = $ipChanges['score'];

        return $factors;
    }

    private function analyzeActivityPattern(int $enrollmentId, int $userId): array
    {
        $activityEvents = DB::table('education_activity_logs')
            ->where('enrollment_id', $enrollmentId)
            ->where('user_id', $userId)
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        $expectedActivity = 50;
        $isAbnormal = $activityEvents > $expectedActivity * 10 || $activityEvents < $expectedActivity * 0.1;
        $score = min($activityEvents / $expectedActivity, 2.0);

        return [
            'is_abnormal' => $isAbnormal,
            'score' => $score,
            'activity_count' => $activityEvents,
        ];
    }

    private function analyzeTimeSpent(int $enrollmentId): array
    {
        $avgTimePerModule = DB::table('education_module_progress')
            ->where('enrollment_id', $enrollmentId)
            ->avg('time_spent_minutes');

        $expectedTime = 60;
        $isSuspicious = $avgTimePerModule !== null && $avgTimePerModule < $expectedTime * 0.2;
        $score = $avgTimePerModule !== null ? max(0, $avgTimePerModule / $expectedTime) : 0;

        return [
            'is_suspicious' => $isSuspicious,
            'score' => $score,
            'avg_time' => $avgTimePerModule,
        ];
    }

    private function analyzeAnswerPattern(int $enrollmentId): array
    {
        $answers = DB::table('education_quiz_answers')
            ->where('enrollment_id', $enrollmentId)
            ->limit(100)
            ->get();

        if ($answers->isEmpty()) {
            return ['has_similar_answers' => false, 'score' => 0];
        }

        $uniqueAnswers = $answers->pluck('answer')->unique()->count();
        $totalAnswers = $answers->count();
        $hasSimilarAnswers = $uniqueAnswers / $totalAnswers < 0.3;
        $score = $uniqueAnswers / $totalAnswers;

        return [
            'has_similar_answers' => $hasSimilarAnswers,
            'score' => $score,
            'unique_ratio' => $uniqueAnswers / $totalAnswers,
        ];
    }

    private function analyzeIpChanges(int $enrollmentId, int $userId): array
    {
        $ips = DB::table('education_activity_logs')
            ->where('enrollment_id', $enrollmentId)
            ->where('user_id', $userId)
            ->where('created_at', '>=', now()->subHours(1))
            ->distinct('ip_address')
            ->count();

        $hasMultipleIps = $ips > 3;
        $score = min($ips / 3, 1.5);

        return [
            'has_multiple_ips' => $hasMultipleIps,
            'score' => $score,
            'ip_count' => $ips,
        ];
    }

    private function calculateCheatingProbability(array $riskFactors): float
    {
        $weights = [
            'abnormal_activity' => 0.3,
            'suspicious_time_spent' => 0.25,
            'similar_answers' => 0.3,
            'multiple_ips' => 0.15,
        ];

        $probability = 0.0;

        foreach ($weights as $factor => $weight) {
            if ($riskFactors[$factor] ?? false) {
                $probability += $weight;
            }
        }

        return min($probability, 1.0);
    }

    private function analyzeReviewFraudIndicators(int $reviewId, int $userId, string $reviewContent): array
    {
        $indicators = [];

        $contentAnalysis = $this->analyzeReviewContent($reviewContent);
        $indicators['generic_content'] = $contentAnalysis['is_generic'];
        $indicators['content_score'] = $contentAnalysis['score'];

        $userBehavior = $this->analyzeUserReviewBehavior($userId);
        $indicators['spam_pattern'] = $userBehavior['is_spam'];
        $indicators['behavior_score'] = $userBehavior['score'];

        $timing = $this->analyzeReviewTiming($reviewId, $userId);
        $indicators['suspicious_timing'] = $timing['is_suspicious'];
        $indicators['timing_score'] = $timing['score'];

        return $indicators;
    }

    private function analyzeReviewContent(string $content): array
    {
        $length = strlen($content);
        $wordCount = str_word_count($content);

        $isGeneric = $length < 50 || $wordCount < 10;
        $score = min($length / 200, 1.0);

        return [
            'is_generic' => $isGeneric,
            'score' => $score,
            'length' => $length,
            'word_count' => $wordCount,
        ];
    }

    private function analyzeUserReviewBehavior(int $userId): array
    {
        $recentReviews = DB::table('education_course_reviews')
            ->where('user_id', $userId)
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        $isSpam = $recentReviews > 10;
        $score = min($recentReviews / 10, 1.0);

        return [
            'is_spam' => $isSpam,
            'score' => $score,
            'recent_count' => $recentReviews,
        ];
    }

    private function analyzeReviewTiming(int $reviewId, int $userId): array
    {
        $enrollment = DB::table('enrollments')
            ->where('user_id', $userId)
            ->where('completed_at', '>=', now()->subMinutes(5))
            ->first();

        $isSuspicious = $enrollment !== null;
        $score = $isSuspicious ? 1.0 : 0.0;

        return [
            'is_suspicious' => $isSuspicious,
            'score' => $score,
        ];
    }

    private function calculateFraudProbability(array $fraudIndicators): float
    {
        $weights = [
            'generic_content' => 0.4,
            'spam_pattern' => 0.35,
            'suspicious_timing' => 0.25,
        ];

        $probability = 0.0;

        foreach ($weights as $indicator => $weight) {
            if ($fraudIndicators[$indicator] ?? false) {
                $probability += $weight;
            }
        }

        return min($probability, 1.0);
    }

    private function determineSeverity(float $probability): string
    {
        return match (true) {
            $probability >= 0.9 => 'critical',
            $probability >= 0.75 => 'high',
            $probability >= 0.5 => 'medium',
            default => 'low',
        };
    }

    private function handleCheatingDetection(CheatingDetectionDto $detection): void
    {
        DB::table('education_fraud_records')->insert([
            'id' => (string) Str::uuid(),
            'tenant_id' => $detection->tenantId,
            'business_group_id' => $detection->businessGroupId,
            'user_id' => $detection->userId,
            'enrollment_id' => $detection->enrollmentId,
            'fraud_type' => 'cheating',
            'severity' => $detection->severity,
            'probability' => $detection->cheatingProbability,
            'risk_factors' => json_encode($detection->riskFactors),
            'correlation_id' => $detection->correlationId,
            'created_at' => now(),
        ]);

        $this->audit->record('education_cheating_detected', 'CheatingDetectionDto', $detection->enrollmentId, [], [
            'correlation_id' => $detection->correlationId,
            'user_id' => $detection->userId,
            'enrollment_id' => $detection->enrollmentId,
            'severity' => $detection->severity,
            'probability' => $detection->cheatingProbability,
        ], $detection->correlationId);

        Log::channel('audit')->warning('Cheating detected', [
            'correlation_id' => $detection->correlationId,
            'user_id' => $detection->userId,
            'enrollment_id' => $detection->enrollmentId,
            'severity' => $detection->severity,
        ]);

        if ($detection->severity === 'critical') {
            $this->blockUser($detection->userId, $detection->tenantId);
        }
    }

    private function handleReviewFraudDetection(ReviewFraudDto $detection): void
    {
        DB::table('education_fraud_records')->insert([
            'id' => (string) Str::uuid(),
            'tenant_id' => $detection->tenantId,
            'business_group_id' => null,
            'user_id' => $detection->userId,
            'review_id' => $detection->reviewId,
            'fraud_type' => 'fake_review',
            'severity' => $detection->severity,
            'probability' => $detection->fraudProbability,
            'risk_factors' => json_encode($detection->fraudIndicators),
            'correlation_id' => $detection->correlationId,
            'created_at' => now(),
        ]);

        DB::table('education_course_reviews')
            ->where('id', $detection->reviewId)
            ->update(['is_flagged' => true, 'flagged_reason' => 'fraud_detected']);

        $this->audit->record('education_review_fraud_detected', 'ReviewFraudDto', $detection->reviewId, [], [
            'correlation_id' => $detection->correlationId,
            'user_id' => $detection->userId,
            'review_id' => $detection->reviewId,
            'severity' => $detection->severity,
            'probability' => $detection->fraudProbability,
        ], $detection->correlationId);

        Log::channel('audit')->warning('Review fraud detected', [
            'correlation_id' => $detection->correlationId,
            'user_id' => $detection->userId,
            'review_id' => $detection->reviewId,
            'severity' => $detection->severity,
        ]);
    }

    private function blockUser(int $userId, int $tenantId): void
    {
        DB::table('users')
            ->where('id', $userId)
            ->update(['is_blocked' => true, 'blocked_at' => now()]);

        Log::channel('audit')->critical('User blocked for cheating', [
            'user_id' => $userId,
            'tenant_id' => $tenantId,
        ]);
    }
}
