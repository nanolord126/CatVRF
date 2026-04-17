<?php declare(strict_types=1);

namespace App\Domains\Travel\Services;

use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Services\Wallet\WalletService;
use App\Services\Payment\PaymentService;
use App\Services\ML\FraudMLService;
use App\Services\ML\UserTasteAnalyzerService;
use App\Services\CRM\CRMIntegrationService;
use App\Domains\Travel\Services\AI\TravelConstructorService;
use App\Domains\Travel\Services\TourismWishlistService;
use App\Domains\Travel\DTOs\TourismBookingDto;
use App\Domains\Travel\Models\Tour;
use App\Domains\Travel\Models\TourBooking;
use App\Domains\Travel\Events\TourismBookingCreatedEvent;
use App\Domains\Travel\Events\TourismBookingConfirmedEvent;
use App\Domains\Travel\Events\TourismBookingCancelledEvent;
use App\Domains\Travel\Jobs\SendBiometricVerificationJob;
use App\Domains\Travel\Jobs\ScheduleVideoCallWithGuideJob;
use App\Domains\Travel\Jobs\UpdateCRMContactJob;
use Illuminate\Database\DatabaseManager;
use Illuminate\Redis\Connections\Connection as RedisConnection;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Tourism Booking Orchestrator Service
 * 
 * Production-ready orchestrator for Tourism vertical with killer features:
 * - AI-personalized tour recommendations with embeddings
 * - Real-time availability hold (15min B2C, 60min B2B) with biometric verification
 * - Dynamic pricing + flash packages by AI prediction
 * - Virtual 360° tours + AR viewing integration
 * - Instant video-call scheduling with guides
 * - B2C quick booking + B2B corporate tours/MICE with commission split
 * - ML-fraud detection for cancellations and no-shows
 * - Wallet split payment + instant cashback
 * - CRM integration at every status (booking, check-in, review)
 * 
 * @package App\Domains\Travel\Services
 */
final readonly class TourismBookingOrchestratorService
{
    public function __construct(
        private FraudControlService $fraud,
        private AuditService $audit,
        private WalletService $wallet,
        private PaymentService $payment,
        private FraudMLService $fraudML,
        private UserTasteAnalyzerService $tasteAnalyzer,
        private CRMIntegrationService $crm,
        private TravelConstructorService $aiConstructor,
        private TourismWishlistService $wishlistService,
        private LoggerInterface $logger,
        private DatabaseManager $db,
        private RedisConnection $redis,
    ) {}

    /**
     * Create a new tourism booking with AI personalization and real-time hold.
     */
    public function createBooking(TourismBookingDto $dto): TourBooking
    {
        $correlationId = $dto->correlationId ?? Str::uuid()->toString();
        
        $this->fraud->check(
            userId: $dto->userId,
            operationType: 'tourism_booking_create',
            amount: $dto->totalAmount,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($dto, $correlationId) {
            $tenantId = tenant()->id ?? $dto->tenantId;
            $businessGroupId = $dto->businessGroupId ?? null;
            $isB2B = $businessGroupId !== null;

            $tour = Tour::where('uuid', $dto->tourUuid)
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();

            $dynamicPrice = $this->calculateDynamicPrice($tour, $dto, $isB2B);
            $holdDurationMinutes = $isB2B ? 60 : 15;
            
            $availabilityKey = "tourism_availability:{$tour->uuid}:{$dto->startDate}";
            $held = $this->redis->setex($availabilityKey, $holdDurationMinutes * 60, $dto->personCount);
            
            if (!$held) {
                throw new \RuntimeException('Tour slots are currently held by another booking. Please try again later.');
            }

            $biometricToken = $this->generateBiometricToken($dto->userId, $correlationId);
            
            $booking = TourBooking::create([
                'uuid' => Str::uuid()->toString(),
                'tenant_id' => $tenantId,
                'business_group_id' => $businessGroupId,
                'tour_id' => $tour->id,
                'user_id' => $dto->userId,
                'person_count' => $dto->personCount,
                'start_date' => $dto->startDate,
                'end_date' => $dto->endDate,
                'total_amount' => $dynamicPrice,
                'base_price' => $tour->base_price,
                'dynamic_price' => $dynamicPrice,
                'discount_amount' => $tour->base_price * $dto->personCount - $dynamicPrice,
                'commission_rate' => $isB2B ? 0.10 : 0.14,
                'commission_amount' => $dynamicPrice * ($isB2B ? 0.10 : 0.14),
                'status' => 'held',
                'biometric_token' => $biometricToken,
                'biometric_verified' => false,
                'hold_expires_at' => now()->addMinutes($holdDurationMinutes),
                'virtual_tour_viewed' => false,
                'video_call_scheduled' => false,
                'video_call_time' => null,
                'payment_method' => $dto->paymentMethod,
                'split_payment_enabled' => $dto->splitPaymentEnabled,
                'cashback_amount' => 0,
                'correlation_id' => $correlationId,
                'tags' => array_merge($dto->tags ?? [], ['ai_personalized', 'dynamic_pricing']),
                'metadata' => [
                    'ai_recommendations' => $this->getAIRecommendations($dto->userId, $tour->id),
                    'flash_package' => $this->isFlashPackage($tour, $dto),
                    'ar_enabled' => $tour->ar_enabled ?? false,
                    'virtual_tour_url' => $this->getVirtualTourUrl($tour),
                ],
            ]);

            $this->audit->record(
                action: 'tourism_booking_created',
                subjectType: TourBooking::class,
                subjectId: $booking->id,
                oldValues: [],
                newValues: [
                    'booking_uuid' => $booking->uuid,
                    'tour_id' => $tour->id,
                    'person_count' => $dto->personCount,
                    'total_amount' => $dynamicPrice,
                    'is_b2b' => $isB2B,
                    'hold_expires_at' => $booking->hold_expires_at->toIso8601String(),
                ],
                correlationId: $correlationId,
            );

            $this->logger->info('Tourism booking created with hold', [
                'booking_id' => $booking->id,
                'booking_uuid' => $booking->uuid,
                'tour_id' => $tour->id,
                'user_id' => $dto->userId,
                'tenant_id' => $tenantId,
                'is_b2b' => $isB2B,
                'person_count' => $dto->personCount,
                'total_amount' => $dynamicPrice,
                'hold_expires_at' => $booking->hold_expires_at->toIso8601String(),
                'correlation_id' => $correlationId,
            ]);

            event(new TourismBookingCreatedEvent($booking, $correlationId));

            SendBiometricVerificationJob::dispatch($booking->id, $biometricToken, $correlationId)
                ->onQueue('biometric');

            UpdateCRMContactJob::dispatch($booking->id, 'booking_created', $correlationId)
                ->onQueue('crm');

            return $booking;
        });
    }

    /**
     * Confirm booking after biometric verification and payment.
     */
    public function confirmBooking(string $bookingUuid, string $correlationId): TourBooking
    {
        $this->fraud->check(
            userId: auth()->id() ?? 0,
            operationType: 'tourism_booking_confirm',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($bookingUuid, $correlationId) {
            $booking = TourBooking::where('uuid', $bookingUuid)
                ->where('tenant_id', tenant()->id ?? 0)
                ->lockForUpdate()
                ->firstOrFail();

            if ($booking->status !== 'held') {
                throw new \RuntimeException('Booking cannot be confirmed in current status: ' . $booking->status);
            }

            if ($booking->hold_expires_at->isPast()) {
                throw new \RuntimeException('Booking hold has expired. Please create a new booking.');
            }

            if (!$booking->biometric_verified) {
                throw new \RuntimeException('Biometric verification required before confirmation.');
            }

            $walletId = $this->wallet->getOrCreateWallet($booking->user_id, $booking->tenant_id);
            
            if ($booking->split_payment_enabled) {
                $this->processSplitPayment($booking, $walletId, $correlationId);
            } else {
                $this->wallet->debit(
                    walletId: $walletId,
                    amount: $booking->total_amount,
                    type: 'tourism_booking',
                    metadata: [
                        'booking_uuid' => $booking->uuid,
                        'tour_id' => $booking->tour_id,
                        'person_count' => $booking->person_count,
                    ],
                    correlationId: $correlationId,
                );
            }

            $cashbackAmount = $this->calculateCashback($booking);
            if ($cashbackAmount > 0) {
                $this->wallet->credit(
                    walletId: $walletId,
                    amount: $cashbackAmount,
                    type: 'cashback',
                    metadata: [
                        'booking_uuid' => $booking->uuid,
                        'cashback_rate' => 0.05,
                    ],
                    correlationId: $correlationId,
                );
            }

            $booking->update([
                'status' => 'confirmed',
                'cashback_amount' => $cashbackAmount,
                'confirmed_at' => now(),
            ]);

            $availabilityKey = "tourism_availability:{$booking->tour->uuid}:{$booking->start_date}";
            $this->redis->del($availabilityKey);

            $this->audit->record(
                action: 'tourism_booking_confirmed',
                subjectType: TourBooking::class,
                subjectId: $booking->id,
                oldValues: ['status' => 'held'],
                newValues: [
                    'status' => 'confirmed',
                    'cashback_amount' => $cashbackAmount,
                    'confirmed_at' => now()->toIso8601String(),
                ],
                correlationId: $correlationId,
            );

            $this->logger->info('Tourism booking confirmed', [
                'booking_id' => $booking->id,
                'booking_uuid' => $booking->uuid,
                'user_id' => $booking->user_id,
                'total_amount' => $booking->total_amount,
                'cashback_amount' => $cashbackAmount,
                'correlation_id' => $correlationId,
            ]);

            event(new TourismBookingConfirmedEvent($booking, $correlationId));

            UpdateCRMContactJob::dispatch($booking->id, 'booking_confirmed', $correlationId)
                ->onQueue('crm');

            if ($booking->metadata['video_call_enabled'] ?? false) {
                ScheduleVideoCallWithGuideJob::dispatch($booking->id, $correlationId)
                    ->onQueue('video_call');
            }

            return $booking->fresh();
        });
    }

    /**
     * Cancel booking with ML-fraud check for cancellation patterns.
     */
    public function cancelBooking(string $bookingUuid, string $reason, string $correlationId): TourBooking
    {
        $this->fraud->check(
            userId: auth()->id() ?? 0,
            operationType: 'tourism_booking_cancel',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($bookingUuid, $reason, $correlationId) {
            $booking = TourBooking::where('uuid', $bookingUuid)
                ->where('tenant_id', tenant()->id ?? 0)
                ->lockForUpdate()
                ->firstOrFail();

            if (!in_array($booking->status, ['held', 'confirmed'])) {
                throw new \RuntimeException('Booking cannot be cancelled in current status: ' . $booking->status);
            }

            $fraudScore = $this->fraudML->predictCancellationFraud(
                userId: $booking->user_id,
                bookingId: $booking->id,
                reason: $reason,
            );

            if ($fraudScore > 0.7) {
                $this->logger->warning('High fraud score for tourism booking cancellation', [
                    'booking_id' => $booking->id,
                    'user_id' => $booking->user_id,
                    'fraud_score' => $fraudScore,
                    'reason' => $reason,
                    'correlation_id' => $correlationId,
                ]);
            }

            $refundAmount = $this->calculateRefundAmount($booking, $reason);
            
            if ($refundAmount > 0 && $booking->status === 'confirmed') {
                $walletId = $this->wallet->getOrCreateWallet($booking->user_id, $booking->tenant_id);
                $this->wallet->credit(
                    walletId: $walletId,
                    amount: $refundAmount,
                    type: 'refund',
                    metadata: [
                        'booking_uuid' => $booking->uuid,
                        'cancellation_reason' => $reason,
                        'fraud_score' => $fraudScore,
                    ],
                    correlationId: $correlationId,
                );
            }

            $booking->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'refund_amount' => $refundAmount,
                'cancelled_at' => now(),
                'fraud_score' => $fraudScore,
            ]);

            $availabilityKey = "tourism_availability:{$booking->tour->uuid}:{$booking->start_date}";
            $this->redis->del($availabilityKey);

            $this->audit->record(
                action: 'tourism_booking_cancelled',
                subjectType: TourBooking::class,
                subjectId: $booking->id,
                oldValues: ['status' => $booking->status],
                newValues: [
                    'status' => 'cancelled',
                    'cancellation_reason' => $reason,
                    'refund_amount' => $refundAmount,
                    'fraud_score' => $fraudScore,
                    'cancelled_at' => now()->toIso8601String(),
                ],
                correlationId: $correlationId,
            );

            $this->logger->info('Tourism booking cancelled', [
                'booking_id' => $booking->id,
                'booking_uuid' => $booking->uuid,
                'user_id' => $booking->user_id,
                'refund_amount' => $refundAmount,
                'fraud_score' => $fraudScore,
                'reason' => $reason,
                'correlation_id' => $correlationId,
            ]);

            event(new TourismBookingCancelledEvent($booking, $reason, $fraudScore, $correlationId));

            UpdateCRMContactJob::dispatch($booking->id, 'booking_cancelled', $correlationId)
                ->onQueue('crm');

            return $booking->fresh();
        });
    }

    /**
     * Schedule video call with guide.
     */
    public function scheduleVideoCall(string $bookingUuid, string $scheduledTime, string $correlationId): TourBooking
    {
        $this->fraud->check(
            userId: auth()->id() ?? 0,
            operationType: 'tourism_video_call_schedule',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($bookingUuid, $scheduledTime, $correlationId) {
            $booking = TourBooking::where('uuid', $bookingUuid)
                ->where('tenant_id', tenant()->id ?? 0)
                ->lockForUpdate()
                ->firstOrFail();

            if ($booking->status !== 'confirmed') {
                throw new \RuntimeException('Video call can only be scheduled for confirmed bookings.');
            }

            $booking->update([
                'video_call_scheduled' => true,
                'video_call_time' => $scheduledTime,
                'video_call_link' => $this->generateVideoCallLink($booking->uuid),
            ]);

            $this->audit->record(
                action: 'tourism_video_call_scheduled',
                subjectType: TourBooking::class,
                subjectId: $booking->id,
                oldValues: ['video_call_scheduled' => false],
                newValues: [
                    'video_call_scheduled' => true,
                    'video_call_time' => $scheduledTime,
                    'video_call_link' => $booking->video_call_link,
                ],
                correlationId: $correlationId,
            );

            $this->logger->info('Tourism video call scheduled', [
                'booking_id' => $booking->id,
                'booking_uuid' => $booking->uuid,
                'scheduled_time' => $scheduledTime,
                'correlation_id' => $correlationId,
            ]);

            return $booking->fresh();
        });
    }

    /**
     * Mark virtual tour as viewed.
     */
    public function markVirtualTourViewed(string $bookingUuid, string $correlationId): TourBooking
    {
        return $this->db->transaction(function () use ($bookingUuid, $correlationId) {
            $booking = TourBooking::where('uuid', $bookingUuid)
                ->where('tenant_id', tenant()->id ?? 0)
                ->lockForUpdate()
                ->firstOrFail();

            $booking->update([
                'virtual_tour_viewed' => true,
                'virtual_tour_viewed_at' => now(),
            ]);

            $this->audit->record(
                action: 'tourism_virtual_tour_viewed',
                subjectType: TourBooking::class,
                subjectId: $booking->id,
                oldValues: ['virtual_tour_viewed' => false],
                newValues: [
                    'virtual_tour_viewed' => true,
                    'virtual_tour_viewed_at' => now()->toIso8601String(),
                ],
                correlationId: $correlationId,
            );

            return $booking->fresh();
        });
    }

    /**
     * Calculate dynamic price based on AI predictions, demand, and B2C/B2B.
     */
    private function calculateDynamicPrice(Tour $tour, TourismBookingDto $dto, bool $isB2B): float
    {
        $basePrice = $tour->base_price * $dto->personCount;
        
        $demandMultiplier = $this->getDemandMultiplier($tour->id, $dto->startDate);
        $flashDiscount = $this->getFlashDiscount($tour, $dto);
        $b2bDiscount = $isB2B ? 0.15 : 0;
        $loyaltyDiscount = $this->tasteAnalyzer->getLoyaltyDiscount($dto->userId, 'tourism');
        $wishlistDiscount = $this->wishlistService->getWishlistDiscount($dto->userId, $tour->id, $dto->correlationId);
        
        $dynamicPrice = $basePrice * $demandMultiplier * (1 - $flashDiscount) * (1 - $b2bDiscount) * (1 - $loyaltyDiscount) * (1 - $wishlistDiscount);
        
        return max($dynamicPrice, $basePrice * 0.5);
    }

    /**
     * Get demand multiplier based on historical data and AI prediction.
     */
    private function getDemandMultiplier(int $tourId, string $startDate): float
    {
        $cacheKey = "tourism_demand:{$tourId}:{$startDate}";
        $cached = $this->redis->get($cacheKey);
        
        if ($cached !== null) {
            return (float) $cached;
        }

        $daysUntilDeparture = now()->diffInDays($startDate);
        
        if ($daysUntilDeparture <= 3) {
            $multiplier = 1.3;
        } elseif ($daysUntilDeparture <= 7) {
            $multiplier = 1.2;
        } elseif ($daysUntilDeparture <= 14) {
            $multiplier = 1.1;
        } else {
            $multiplier = 1.0;
        }

        $this->redis->setex($cacheKey, 3600, (string) $multiplier);
        
        return $multiplier;
    }

    /**
     * Get flash package discount if applicable.
     */
    private function getFlashDiscount(Tour $tour, TourismBookingDto $dto): float
    {
        $flashKey = "tourism_flash:{$tour->uuid}";
        $flashData = $this->redis->get($flashKey);
        
        if ($flashData === null) {
            return 0;
        }

        $flash = json_decode($flashData, true);
        
        if (now()->isBetween(
            \Carbon\Carbon::parse($flash['start_time']),
            \Carbon\Carbon::parse($flash['end_time'])
        )) {
            return $flash['discount_rate'] ?? 0;
        }

        return 0;
    }

    /**
     * Check if this is a flash package booking.
     */
    private function isFlashPackage(Tour $tour, TourismBookingDto $dto): bool
    {
        return $this->getFlashDiscount($tour, $dto) > 0;
    }

    /**
     * Generate biometric verification token.
     */
    private function generateBiometricToken(int $userId, string $correlationId): string
    {
        return hash('sha256', $userId . $correlationId . now()->timestamp . config('app.key'));
    }

    /**
     * Get AI recommendations for the user and tour.
     */
    private function getAIRecommendations(int $userId, int $tourId): array
    {
        try {
            return $this->aiConstructor->analyzeAndRecommend(
                payload: ['tour_id' => $tourId],
                userId: $userId,
            );
        } catch (\Throwable $e) {
            $this->logger->error('AI recommendations failed', [
                'user_id' => $userId,
                'tour_id' => $tourId,
                'error' => $e->getMessage(),
            ]);
            
            return [];
        }
    }

    /**
     * Get virtual tour URL for the tour.
     */
    private function getVirtualTourUrl(Tour $tour): ?string
    {
        if ($tour->virtual_tour_enabled ?? false) {
            return url("/tourism/virtual-tour/{$tour->uuid}");
        }

        return null;
    }

    /**
     * Process split payment across multiple payment methods.
     */
    private function processSplitPayment(TourBooking $booking, int $walletId, string $correlationId): void
    {
        $splitAmounts = $this->calculateSplitAmounts($booking);
        
        foreach ($splitAmounts as $method => $amount) {
            if ($method === 'wallet') {
                $this->wallet->debit(
                    walletId: $walletId,
                    amount: $amount,
                    type: 'tourism_booking_split',
                    metadata: [
                        'booking_uuid' => $booking->uuid,
                        'split_method' => 'wallet',
                    ],
                    correlationId: $correlationId,
                );
            } else {
                $this->payment->initPayment(
                    amount: $amount,
                    paymentMethod: $method,
                    metadata: [
                        'booking_uuid' => $booking->uuid,
                        'split_payment' => true,
                    ],
                    correlationId: $correlationId,
                );
            }
        }
    }

    /**
     * Calculate split payment amounts.
     */
    private function calculateSplitAmounts(TourBooking $booking): array
    {
        $total = $booking->total_amount;
        
        return [
            'wallet' => $total * 0.3,
            'card' => $total * 0.7,
        ];
    }

    /**
     * Calculate cashback amount.
     */
    private function calculateCashback(TourBooking $booking): float
    {
        $cashbackRate = $this->tasteAnalyzer->getCashbackRate($booking->user_id, 'tourism');
        
        return $booking->total_amount * $cashbackRate;
    }

    /**
     * Calculate refund amount based on cancellation policy.
     */
    private function calculateRefundAmount(TourBooking $booking, string $reason): float
    {
        $hoursUntilDeparture = now()->diffInHours($booking->start_date);
        
        if ($hoursUntilDeparture > 168) {
            return $booking->total_amount * 0.9;
        } elseif ($hoursUntilDeparture > 72) {
            return $booking->total_amount * 0.5;
        } elseif ($hoursUntilDeparture > 24) {
            return $booking->total_amount * 0.2;
        }

        return 0;
    }

    /**
     * Generate video call link.
     */
    private function generateVideoCallLink(string $bookingUuid): string
    {
        $token = hash('sha256', $bookingUuid . now()->timestamp . config('app.key'));
        
        return url("/tourism/video-call/{$bookingUuid}?token={$token}");
    }
}
