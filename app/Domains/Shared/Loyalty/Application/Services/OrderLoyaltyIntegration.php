<?php

declare(strict_types=1);

namespace Modules\Loyalty\Application\Services;

use Illuminate\Support\Facades\Log;
use Modules\Loyalty\Application\DTOs\EnrollGuestDTO;
use Modules\Loyalty\Domain\ValueObjects\CurrencyAmount;
use App\Traits\WithAuditLogging;
use App\Services\Audit\AuditService;

final readonly class OrderLoyaltyIntegration
{
    use WithAuditLogging;

    public function __construct(
        private LoyaltyService $loyaltyService,
        private readonly AuditService $audit,
    ) {
    }

    /**
     * Process loyalty points after order completion
     */
    public function processOrderCompletion(
        int $guestId,
        string $programId,
        float $orderAmount,
        ?string $tierSlug = null,
        ?string $orderUuid = null,
        ?int $orderId = null
    ): void {
        try {
            $this->loyaltyService->processOrderLoyalty(
                guestId: $guestId,
                programId: $programId,
                orderAmount: CurrencyAmount::fromFloat($orderAmount),
                tierSlug: $tierSlug,
                sourceType: 'order',
                sourceId: $orderId
            );

            Log::info('Loyalty points processed for order', [
                'guest_id' => $guestId,
                'program_id' => $programId,
                'order_amount' => $orderAmount,
                'order_uuid' => $orderUuid,
            ]);
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
     * Process loyalty points refund when order is cancelled/refunded
     */
    public function processOrderRefund(
        int $guestId,
        string $programId,
        float $refundedAmount,
        ?int $orderId = null
    ): void {
        try {
            // Points are typically not refunded automatically, but this can be configured
            // This is a placeholder for refund logic if needed
            Log::info('Order refund processed', [
                'guest_id' => $guestId,
                'program_id' => $programId,
                'refunded_amount' => $refundedAmount,
                'order_id' => $orderId,
            ]);

            $this->logAction('loyalty_order_refunded', 'OrderLoyaltyIntegration', $orderId, [
                'guest_id' => $guestId,
                'program_id' => $programId,
                'refunded_amount' => $refundedAmount,
            ], $guestId, null);
        } catch (\Exception $e) {
            Log::error('Failed to process loyalty points refund', [
                'guest_id' => $guestId,
                'program_id' => $programId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
