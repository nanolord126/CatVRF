<?php

declare(strict_types=1);

namespace Modules\Loyalty\Application\Services;

use Illuminate\Support\Facades\Log;
use Modules\Loyalty\Application\DTOs\EnrollGuestDTO;
use Modules\Loyalty\Domain\ValueObjects\CurrencyAmount;
use App\Traits\WithAuditLogging;
use App\Services\Audit\AuditService;

final readonly class BookingLoyaltyIntegration
{
    use WithAuditLogging;

    public function __construct(
        private LoyaltyService $loyaltyService,
        private readonly AuditService $audit,
    ) {
    }

    /**
     * Process loyalty points after booking completion (check-out)
     */
    public function processBookingCompletion(
        int $guestId,
        string $programId,
        float $bookingAmount,
        ?string $tierSlug = null,
        ?string $bookingUuid = null,
        ?int $bookingId = null
    ): void {
        try {
            $this->loyaltyService->processOrderLoyalty(
                guestId: $guestId,
                programId: $programId,
                orderAmount: CurrencyAmount::fromFloat($bookingAmount),
                tierSlug: $tierSlug,
                sourceType: 'booking',
                sourceId: $bookingId
            );

            Log::info('Loyalty points processed for booking', [
                'guest_id' => $guestId,
                'program_id' => $programId,
                'booking_amount' => $bookingAmount,
                'booking_uuid' => $bookingUuid,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process loyalty points for booking', [
                'guest_id' => $guestId,
                'program_id' => $programId,
                'booking_uuid' => $bookingUuid,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Enroll guest in loyalty program automatically
     */
    public function autoEnrollGuest(
        int $guestId,
        string $programId,
        ?int $userId = null,
        ?string $birthday = null,
        ?int $referredBy = null
    ): void {
        try {
            $dto = EnrollGuestDTO::fromArray([
                'program_id' => $programId,
                'guest_id' => $guestId,
                'user_id' => $userId,
                'birthday' => $birthday,
                'referred_by' => $referredBy,
            ]);

            $this->loyaltyService->enrollGuest($dto);

            Log::info('Guest auto-enrolled in loyalty program', [
                'guest_id' => $guestId,
                'program_id' => $programId,
            ]);

            $this->logAction('loyalty_auto_enrolled', 'GuestLoyaltyProfile', $guestId, [
                'guest_id' => $guestId,
                'program_id' => $programId,
                'referred_by' => $referredBy,
            ], $userId, null);
        } catch (\DomainException $e) {
            // Guest already enrolled, which is fine
            Log::debug('Guest already enrolled in loyalty program', [
                'guest_id' => $guestId,
                'program_id' => $programId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to auto-enroll guest in loyalty program', [
                'guest_id' => $guestId,
                'program_id' => $programId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Apply loyalty discount to booking
     */
    public function applyLoyaltyDiscount(
        int $guestId,
        string $programId,
        float $bookingAmount,
        ?string $tierSlug = null
    ): array {
        try {
            // Calculate discount based on tier
            $discountAmount = 0.0;
            $discountPercentage = 0.0;

            if ($tierSlug) {
                $profile = $this->loyaltyService->getProfileBalance($guestId, $programId);
                // Add tier discount logic here
            }

            return [
                'discount_amount' => $discountAmount,
                'discount_percentage' => $discountPercentage,
                'final_amount' => $bookingAmount - $discountAmount,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to apply loyalty discount', [
                'guest_id' => $guestId,
                'program_id' => $programId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
