<?php declare(strict_types=1);

namespace App\Services\Consulting;


use Illuminate\Http\Request;
use App\Models\Consulting\Consultant;
use App\Models\Consulting\ConsultingFirm;
use App\Models\Consulting\ConsultingReview;
use App\Models\Consulting\ConsultingSession;
use App\Services\FraudControlService;
use Illuminate\Support\Collection;


use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Auth\Guard;

final readonly class ConsultingRatingService
{

    /**
         * @param string $correlationId Unified audit trace.
         */
        public function __construct(
        private readonly Request $request,
            private readonly FraudControlService $fraud,
            private readonly LogManager $logger,
            private readonly DatabaseManager $db,
            private readonly Guard $guard,
    ) {}

        private function correlationId(): string
        {
            return $this->request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        }

        /**
         * Submit a review for a consulting session.
         */
        public function submitSessionReview(int $sessionId, int $rating, string $comment, int $userId): ConsultingReview
        {
            $this->fraud->check((int) $this->guard->id(), 'consulting_submit_review', $this->request->ip());

            return $this->db->transaction(function() use ($sessionId, $rating, $comment, $userId) {
                $session = ConsultingSession::findOrFail($sessionId);

                if ($session->client_id !== $userId) {
                    throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException('User did not participate in this session.');
                }

                if ($session->status !== 'completed') {
                    throw new \LogicException('Review must be submitted for a completed session.');
                }

                $this->logger->channel('audit')->info('Submitting Consulting Review', [
                    'session_id' => $sessionId,
                    'rating' => $rating,
                    'correlation_id' => $this->correlationId(),
                ]);

                $review = ConsultingReview::create([
                   'tenant_id' => $session->tenant_id,
                   'consulting_session_id' => $sessionId,
                   'consultant_id' => $session->consultant_id,
                   'client_id' => $userId,
                   'rating' => $rating,
                   'comment' => $comment,
                   'is_verified' => true, // Auto-verified if linked to a completed session
                   'correlation_id' => $this->correlationId(),
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
            $this->logger->channel('audit')->info('Recalculating Consultant Rating', [
                'consultant_id' => $consultantId,
                'correlation_id' => $this->correlationId(),
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
            $this->logger->channel('audit')->info('Recalculating Firm Rating', [
                'firm_id' => $firmId,
                'correlation_id' => $this->correlationId(),
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
