<?php declare(strict_types=1);

namespace App\Domains\Travel\DTOs;

use Illuminate\Http\Request;

/**
 * Tourism Booking Data Transfer Object
 * 
 * Encapsulates all data required for creating a tourism booking.
 * Follows CatVRF 2026 canonical rules for DTOs.
 */
final readonly class TourismBookingDto
{
    public function __construct(
        public int $tenantId,
        public ?int $businessGroupId,
        public int $userId,
        public string $tourUuid,
        public int $personCount,
        public string $startDate,
        public string $endDate,
        public float $totalAmount,
        public string $paymentMethod,
        public bool $splitPaymentEnabled,
        public string $correlationId,
        public ?array $tags = null,
        public ?array $metadata = null,
    ) {}

    /**
     * Create DTO from HTTP request.
     */
    public static function fromRequest(Request $request): self
    {
        $isB2B = $request->has('inn') && $request->has('business_card_id');
        
        $tenantId = (int) $request->input('tenant_id', 1);
        
        return new self(
            tenantId: $tenantId,
            businessGroupId: $isB2B ? (int) $request->input('business_group_id') : null,
            userId: (int) $request->input('user_id', auth()->id() ?? 0),
            tourUuid: (string) $request->input('tour_uuid'),
            personCount: (int) $request->input('person_count'),
            startDate: (string) $request->input('start_date'),
            endDate: (string) $request->input('end_date'),
            totalAmount: (float) $request->input('total_amount'),
            paymentMethod: (string) $request->input('payment_method', 'card'),
            splitPaymentEnabled: (bool) $request->input('split_payment_enabled', false),
            correlationId: (string) $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
            tags: $request->input('tags'),
            metadata: $request->input('metadata'),
        );
    }

    /**
     * Convert DTO to array.
     */
    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'user_id' => $this->userId,
            'tour_uuid' => $this->tourUuid,
            'person_count' => $this->personCount,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'total_amount' => $this->totalAmount,
            'payment_method' => $this->paymentMethod,
            'split_payment_enabled' => $this->splitPaymentEnabled,
            'correlation_id' => $this->correlationId,
            'tags' => $this->tags,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Check if this is a B2B booking.
     */
    public function isB2B(): bool
    {
        return $this->businessGroupId !== null;
    }
}
