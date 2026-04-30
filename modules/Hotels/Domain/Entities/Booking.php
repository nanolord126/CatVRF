<?php

declare(strict_types=1);

namespace Modules\Hotels\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class Booking
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public int $venueId,
        public int $guestId,
        public ?int $userId,
        public string $uuid,
        public string $confirmationCode,
        public string $status,
        public string $source,
        public ?string $externalReference,
        public CarbonImmutable $checkInDate,
        public CarbonImmutable $checkOutDate,
        public int $adults,
        public int $children,
        public int $infants,
        public float $totalAmount,
        public float $paidAmount,
        public float $depositAmount,
        public string $currency,
        public string $paymentStatus,
        public ?array $specialRequests,
        public ?array $roomPreferences,
        public bool $earlyCheckinRequested,
        public bool $lateCheckoutRequested,
        public ?CarbonImmutable $actualCheckIn,
        public ?CarbonImmutable $actualCheckOut,
        public ?int $createdBy,
        public ?int $modifiedBy,
        public ?string $cancellationReason,
        public ?CarbonImmutable $cancelledAt,
        public ?array $notes,
        public ?string $correlationId,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $venueId,
        int $guestId,
        CarbonImmutable $checkInDate,
        CarbonImmutable $checkOutDate,
        int $adults = 1,
        int $children = 0,
        int $infants = 0,
        ?int $userId = null,
        string $source = 'direct',
        ?string $externalReference = null,
        float $totalAmount = 0.0,
        float $depositAmount = 0.0,
        string $currency = 'RUB',
        ?array $specialRequests = null,
        ?array $roomPreferences = null,
        bool $earlyCheckinRequested = false,
        bool $lateCheckoutRequested = false,
        ?int $createdBy = null,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            venueId: $venueId,
            guestId: $guestId,
            userId: $userId,
            uuid: (string) \Illuminate\Support\Str::uuid(),
            confirmationCode: self::generateConfirmationCode(),
            status: 'pending',
            source: $source,
            externalReference: $externalReference,
            checkInDate: $checkInDate,
            checkOutDate: $checkOutDate,
            adults: $adults,
            children: $children,
            infants: $infants,
            totalAmount: $totalAmount,
            paidAmount: 0.0,
            depositAmount: $depositAmount,
            currency: $currency,
            paymentStatus: 'pending',
            specialRequests: $specialRequests,
            roomPreferences: $roomPreferences,
            earlyCheckinRequested: $earlyCheckinRequested,
            lateCheckoutRequested: $lateCheckoutRequested,
            actualCheckIn: null,
            actualCheckOut: null,
            createdBy: $createdBy,
            modifiedBy: $createdBy,
            cancellationReason: null,
            cancelledAt: null,
            notes: null,
            correlationId: null,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    private static function generateConfirmationCode(): string
    {
        return 'CNF-' . strtoupper(\Illuminate\Support\Str::random(8));
    }

    public function getNights(): int
    {
        return $this->checkInDate->diffInDays($this->checkOutDate);
    }

    public function markPrepaid(): self
    {
        return new self(
            ...get_object_vars($this),
            status: 'prepaid',
            paymentStatus: 'partially_paid',
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function markPaid(): self
    {
        return new self(
            ...get_object_vars($this),
            status: 'paid',
            paymentStatus: 'paid',
            paidAmount: $this->totalAmount,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function checkIn(): self
    {
        return new self(
            ...get_object_vars($this),
            status: 'checked_in',
            actualCheckIn: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function checkOut(): self
    {
        return new self(
            ...get_object_vars($this),
            status: 'checked_out',
            actualCheckOut: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function cancel(string $reason): self
    {
        return new self(
            ...get_object_vars($this),
            status: 'cancelled',
            cancellationReason: $reason,
            cancelledAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function markNoShow(): self
    {
        return $this->cancel('No show');
    }

    public function addPayment(float $amount): self
    {
        $newPaidAmount = $this->paidAmount + $amount;
        $paymentStatus = $newPaidAmount >= $this->totalAmount 
            ? 'paid' 
            : ($newPaidAmount > 0 ? 'partially_paid' : 'pending');

        return new self(
            ...get_object_vars($this),
            paidAmount: $newPaidAmount,
            paymentStatus: $paymentStatus,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function isFullyPaid(): bool
    {
        return $this->paymentStatus === 'paid';
    }

    public function isPartiallyPaid(): bool
    {
        return $this->paymentStatus === 'partially_paid';
    }

    public function canCheckIn(): bool
    {
        return in_array($this->status, ['prepaid', 'paid']) && $this->checkInDate->isPast();
    }

    public function canCancel(): bool
    {
        return in_array($this->status, ['pending', 'prepaid']) && $this->checkInDate->isFuture();
    }
}
