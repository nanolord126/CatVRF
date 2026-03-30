<?php declare(strict_types=1);

namespace App\Services\Consulting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ConsultingRatingService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * @param string $correlationId Unified audit trace.
         */
        public function __construct(
            private string $correlationId = '',
        ) {
            $this->correlationId = $correlationId ?: (string) Str::uuid();
        }

        /**
         * Submit a review for a consulting session.
         */
        public function submitSessionReview(int $sessionId, int $rating, string $comment, int $userId): ConsultingReview
        {
            FraudControlService::check();

            return DB::transaction(function() use ($sessionId, $rating, $comment, $userId) {
                $session = ConsultingSession::findOrFail($sessionId);

                if ($session->client_id !== $userId) {
                    throw new \Exception("Unauthorized: User did not participate in this session.");
                }

                if ($session->status !== 'completed') {
                    throw new \Exception("Review must be submitted for a completed session.");
                }

                Log::channel('audit')->info('Submitting Consulting Review', [
                    'session_id' => $sessionId,
                    'rating' => $rating,
                    'correlation_id' => $this->correlationId,
                ]);

                $review = ConsultingReview::create([
                   'tenant_id' => $session->tenant_id,
                   'consulting_session_id' => $sessionId,
                   'consultant_id' => $session->consultant_id,
                   'client_id' => $userId,
                   'rating' => $rating,
                   'comment' => $comment,
                   'is_verified' => true, // Auto-verified if linked to a completed session
                   'correlation_id' => $this->correlationId,
                ]);

                // Logic for recalculating expert rating
                $this->recalculateConsultantRating($session->consultant_id);

                return $review;
            });
        }

        /**
         * Perform consultant rating recalculation logic.
         */
        public function recalculateConsultantRating(int $consultantId): void
        {
            Log::channel('audit')->info('Recalculating Consultant Rating', [
                'consultant_id' => $consultantId,
                'correlation_id' => $this->correlationId,
            ]);

            $consultant = Consultant::findOrFail($consultantId);

            $avgRating = (int) ConsultingReview::where('consultant_id', $consultantId)
                 ->verified()
                 ->avg('rating');

            $consultant->update(['rating' => $avgRating]);

            // Propagate to firm
            if ($consultant->consulting_firm_id) {
                 $this->recalculateFirmRating($consultant->consulting_firm_id);
            }
        }

        /**
         * Perform firm rating recalculation logic.
         */
        public function recalculateFirmRating(int $firmId): void
        {
            Log::channel('audit')->info('Recalculating Firm Rating', [
                'firm_id' => $firmId,
                'correlation_id' => $this->correlationId,
            ]);

            $firm = ConsultingFirm::findOrFail($firmId);

            $avgConsultantRating = (int) Consultant::where('consulting_firm_id', $firmId)
                 ->avg('rating');

            $firm->update(['rating' => $avgConsultantRating]);
        }

        /**
         * AI-based Sentiment Analysis for review comments.
         */
        public function analyzeReviewSentiment(int $reviewId): string
        {
            $review = ConsultingReview::findOrFail($reviewId);

            // Mocking AI sentiment score 0-1
            $score = rand(0, 100) / 100;

            return match(true) {
                $score > 0.8 => 'extremely_positive',
                $score > 0.6 => 'positive',
                $score > 0.4 => 'neutral',
                default => 'negative_review_requires_response',
            };
        }

        /**
         * Get verified reviews for a consultant.
         */
        public function getExpertReviews(int $consultantId): Collection
        {
            return ConsultingReview::where('consultant_id', $consultantId)
                 ->verified()
                 ->get();
        }
}
