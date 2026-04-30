<?php

declare(strict_types=1);

namespace Modules\Hotels\Application\DTOs;

final readonly class MarketplaceBookingDTO
{
    public function __construct(
        public string $externalId,
        public string $confirmationCode,
        public string $source,
        public int $tenantId,
        public int $venueId,
        public string $roomCode,
        public string $checkInDate,
        public string $checkOutDate,
        public int $adults,
        public ?int $children,
        public ?int $infants,
        public float $totalAmount,
        public ?float $paidAmount,
        public ?float $depositAmount,
        public string $currency,
        public string $paymentStatus,
        public string $status,
        public string $guestFirstName,
        public string $guestLastName,
        public string $guestEmail,
        public string $guestPhone,
        public ?string $guestNationality,
        public ?string $guestLanguage,
        public ?string $specialRequests,
    ) {}

    public static function fromBookingCom(array $data): self
    {
        return new self(
            externalId: $data['reservation']['id'] ?? throw new \InvalidArgumentException('Missing reservation.id'),
            confirmationCode: $data['reservation']['confirmation_code'] ?? '',
            source: 'booking_com',
            tenantId: $data['tenant_id'] ?? 1,
            venueId: $data['hotel_id'] ?? throw new \InvalidArgumentException('Missing hotel_id'),
            roomCode: $data['reservation']['room']['code'] ?? throw new \InvalidArgumentException('Missing room.code'),
            checkInDate: $data['reservation']['checkin'] ?? throw new \InvalidArgumentException('Missing checkin'),
            checkOutDate: $data['reservation']['checkout'] ?? throw new \InvalidArgumentException('Missing checkout'),
            adults: $data['reservation']['guests']['adults'] ?? 1,
            children: $data['reservation']['guests']['children'] ?? 0,
            infants: $data['reservation']['guests']['infants'] ?? 0,
            totalAmount: (float) ($data['reservation']['price'] ?? 0),
            paidAmount: (float) ($data['reservation']['paid_amount'] ?? 0),
            depositAmount: (float) ($data['reservation']['deposit'] ?? 0),
            currency: $data['reservation']['currency'] ?? 'RUB',
            paymentStatus: $data['reservation']['payment_status'] ?? 'pending',
            status: $data['reservation']['status'] ?? 'new',
            guestFirstName: $data['reservation']['customer']['first_name'] ?? '',
            guestLastName: $data['reservation']['customer']['last_name'] ?? '',
            guestEmail: $data['reservation']['customer']['email'] ?? '',
            guestPhone: $data['reservation']['customer']['phone'] ?? '',
            guestNationality: $data['reservation']['customer']['nationality'] ?? null,
            guestLanguage: $data['reservation']['customer']['language'] ?? 'ru',
            specialRequests: $data['reservation']['comments'] ?? null,
        );
    }

    public static function fromOstrovok(array $data): self
    {
        return new self(
            externalId: $data['order']['id'] ?? throw new \InvalidArgumentException('Missing order.id'),
            confirmationCode: $data['order']['confirmation_code'] ?? '',
            source: 'ostrovok',
            tenantId: $data['tenant_id'] ?? 1,
            venueId: $data['hotel_id'] ?? throw new \InvalidArgumentException('Missing hotel_id'),
            roomCode: $data['order']['room_type_id'] ?? throw new \InvalidArgumentException('Missing room_type_id'),
            checkInDate: $data['order']['arrival_date'] ?? throw new \InvalidArgumentException('Missing arrival_date'),
            checkOutDate: $data['order']['departure_date'] ?? throw new \InvalidArgumentException('Missing departure_date'),
            adults: $data['order']['guests'] ?? 1,
            children: $data['order']['children'] ?? 0,
            infants: 0,
            totalAmount: (float) ($data['order']['price'] ?? 0),
            paidAmount: (float) ($data['order']['paid_amount'] ?? 0),
            depositAmount: (float) ($data['order']['deposit'] ?? 0),
            currency: $data['order']['currency'] ?? 'RUB',
            paymentStatus: $data['order']['payment_status'] ?? 'pending',
            status: $data['order']['status'] ?? 'new',
            guestFirstName: $data['order']['client']['name'] ?? '',
            guestLastName: '', // Ostrovok has single name field
            guestEmail: $data['order']['client']['email'] ?? '',
            guestPhone: $data['order']['client']['phone'] ?? '',
            guestNationality: $data['order']['client']['nationality'] ?? null,
            guestLanguage: $data['order']['client']['language'] ?? 'ru',
            specialRequests: $data['order']['wishes'] ?? null,
        );
    }

    public static function fromAirbnb(array $data): self
    {
        return new self(
            externalId: $data['reservation']['code'] ?? throw new \InvalidArgumentException('Missing reservation.code'),
            confirmationCode: $data['reservation']['code'] ?? '',
            source: 'airbnb',
            tenantId: $data['tenant_id'] ?? 1,
            venueId: $data['listing_id'] ?? throw new \InvalidArgumentException('Missing listing_id'),
            roomCode: $data['listing_id'] ?? throw new \InvalidArgumentException('Missing listing_id'),
            checkInDate: $data['reservation']['start_date'] ?? throw new \InvalidArgumentException('Missing start_date'),
            checkOutDate: $data['reservation']['end_date'] ?? throw new \InvalidArgumentException('Missing end_date'),
            adults: $data['reservation']['guests'] ?? 1,
            children: 0,
            infants: 0,
            totalAmount: (float) ($data['reservation']['total_price'] ?? 0),
            paidAmount: (float) ($data['reservation']['total_price'] ?? 0),
            depositAmount: 0,
            currency: $data['reservation']['currency'] ?? 'RUB',
            paymentStatus: $data['reservation']['status'] === 'confirmed' ? 'paid' : 'pending',
            status: $data['reservation']['status'] ?? 'pending',
            guestFirstName: $data['guest']['first_name'] ?? '',
            guestLastName: $data['guest']['last_name'] ?? '',
            guestEmail: $data['guest']['email'] ?? '',
            guestPhone: $data['guest']['phone'] ?? '',
            guestNationality: $data['guest']['nationality'] ?? null,
            guestLanguage: $data['guest']['language'] ?? 'ru',
            specialRequests: $data['reservation']['notes'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'external_id' => $this->externalId,
            'confirmation_code' => $this->confirmationCode,
            'source' => $this->source,
            'tenant_id' => $this->tenantId,
            'venue_id' => $this->venueId,
            'room_code' => $this->roomCode,
            'check_in_date' => $this->checkInDate,
            'check_out_date' => $this->checkOutDate,
            'adults' => $this->adults,
            'children' => $this->children,
            'infants' => $this->infants,
            'total_amount' => $this->totalAmount,
            'paid_amount' => $this->paidAmount,
            'deposit_amount' => $this->depositAmount,
            'currency' => $this->currency,
            'payment_status' => $this->paymentStatus,
            'status' => $this->status,
            'guest_first_name' => $this->guestFirstName,
            'guest_last_name' => $this->guestLastName,
            'guest_email' => $this->guestEmail,
            'guest_phone' => $this->guestPhone,
            'guest_nationality' => $this->guestNationality,
            'guest_language' => $this->guestLanguage,
            'special_requests' => $this->specialRequests,
        ];
    }
}
