<?php

declare(strict_types=1);

namespace Modules\Hotels\Application\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Hotels\Application\DTOs\MarketplaceBookingDTO;
use Modules\Hotels\Domain\Entities\Booking;
use Modules\Hotels\Domain\Entities\Guest;
use Modules\Hotels\Domain\Repositories\BookingRepositoryInterface;
use Modules\Hotels\Domain\Repositories\GuestRepositoryInterface;
use Modules\Hotels\Domain\Repositories\RoomRepositoryInterface;
use Modules\Hotels\Domain\Repositories\VenueRepositoryInterface;
use Modules\Hotels\Infrastructure\Models\ExternalBookingReferenceModel;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;

final readonly class MarketplaceIntegrationService
{
    use WithAuditLogging;

    public function __construct(
        private readonly BookingRepositoryInterface $bookingRepository,
        private readonly GuestRepositoryInterface $guestRepository,
        private readonly RoomRepositoryInterface $roomRepository,
        private readonly VenueRepositoryInterface $venueRepository,
        private readonly BookingService $bookingService,
        private readonly AuditService $auditService,
    ) {}

    public function syncBookingFromMarketplace(
        string $source,
        MarketplaceBookingDTO $dto
    ): ExternalBookingReferenceModel {
        return DB::transaction(function () use ($source, $dto) {
            try {
                // Check if external booking already exists
                $existingReference = ExternalBookingReferenceModel::where('source', $source)
                    ->where('external_id', $dto->externalId)
                    ->first();

                if ($existingReference && $existingReference->sync_status === 'synced') {
                    return $existingReference;
                }

                // Find or create guest
                $guest = $this->findOrCreateGuest($dto);

                // Find room by external mapping
                $room = $this->roomRepository->findByExternalCode($dto->roomCode, $dto->venueId);
                if (!$room) {
                    throw new \RuntimeException("Room not found for code: {$dto->roomCode}");
                }

                // Map status from marketplace to CRM
                $crmStatus = $this->mapStatus($source, $dto->status);

                // Create booking
                $booking = $this->bookingService->createBooking([
                    'tenant_id' => $dto->tenantId,
                    'venue_id' => $dto->venueId,
                    'guest_id' => $guest->getId(),
                    'check_in_date' => CarbonImmutable::parse($dto->checkInDate),
                    'check_out_date' => CarbonImmutable::parse($dto->checkOutDate),
                    'adults' => $dto->adults,
                    'children' => $dto->children ?? 0,
                    'infants' => $dto->infants ?? 0,
                    'total_amount' => $dto->totalAmount,
                    'paid_amount' => $dto->paidAmount ?? 0,
                    'deposit_amount' => $dto->depositAmount ?? 0,
                    'currency' => $dto->currency,
                    'payment_status' => $this->mapPaymentStatus($dto->paymentStatus),
                    'source' => $source,
                    'special_requests' => $dto->specialRequests,
                    'room_preferences' => null,
                    'created_by' => 0, // System user
                    'confirmation_code' => $dto->confirmationCode,
                ]);

                // Add booking item
                $this->bookingService->addBookingItem(
                    $booking->getId(),
                    $room->getId(),
                    $dto->totalAmount
                );

                // Update payment status if paid
                if ($dto->paymentStatus === 'paid' || $dto->paymentStatus === 'confirmed') {
                    $this->bookingService->updatePaymentStatus($booking->getId(), 'paid', $dto->totalAmount);
                }

                // Confirm booking if status requires
                if (in_array($crmStatus, ['prepaid', 'confirmed', 'paid'])) {
                    $this->bookingService->confirmBooking($booking->getId());
                }

                // Create or update external reference
                $reference = ExternalBookingReferenceModel::updateOrCreate(
                    [
                        'source' => $source,
                        'external_id' => $dto->externalId,
                    ],
                    [
                        'booking_id' => $booking->getId(),
                        'external_confirmation_code' => $dto->confirmationCode,
                        'sync_status' => 'synced',
                        'synced_at' => now(),
                        'last_sync_error' => null,
                        'raw_data' => json_encode($dto->toArray()),
                    ]
                );

                Log::channel('marketplace-sync')->info('Booking synced successfully', [
                    'source' => $source,
                    'external_id' => $dto->externalId,
                    'booking_id' => $booking->getId(),
                ]);

                return $reference;
            } catch (\Throwable $e) {
                Log::channel('marketplace-sync')->error('Booking sync failed', [
                    'source' => $source,
                    'external_id' => $dto->externalId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Update reference with error
                ExternalBookingReferenceModel::updateOrCreate(
                    [
                        'source' => $source,
                        'external_id' => $dto->externalId,
                    ],
                    [
                        'sync_status' => 'failed',
                        'last_sync_error' => $e->getMessage(),
                        'raw_data' => json_encode($dto->toArray()),
                    ]
                );

                throw $e;
            }
        });
    }

    public function pushBookingToMarketplace(
        int $bookingId,
        string $targetMarketplace
    ): ExternalBookingReferenceModel {
        // Implementation for pushing CRM bookings to marketplaces
        // This would involve API calls to Booking.com, Ostrovok, etc.
        throw new \RuntimeException('Push to marketplace not implemented yet');
    }

    public function syncAvailabilityToMarketplace(
        int $venueId,
        string $marketplace
    ): void {
        $venue = $this->venueRepository->findById($venueId);
        if (!$venue) {
            throw new \RuntimeException("Venue not found: {$venueId}");
        }

        // Get current room availability from CRM
        $rooms = $this->roomRepository->findByVenueId($venueId);
        $availability = [];

        foreach ($rooms as $room) {
            $availability[] = [
                'room_code' => $room->getExternalCode(),
                'status' => $room->getStatus(),
                'clean_status' => $room->getCleanStatus(),
            ];
        }

        // Push to marketplace API
        // Implementation depends on marketplace API
        Log::channel('marketplace-sync')->info('Availability sync initiated', [
            'venue_id' => $venueId,
            'marketplace' => $marketplace,
            'rooms_count' => count($availability),
        ]);
    }

    public function syncRatesToMarketplace(
        int $venueId,
        string $marketplace
    ): void {
        $venue = $this->venueRepository->findById($venueId);
        if (!$venue) {
            throw new \RuntimeException("Venue not found: {$venueId}");
        }

        // Get current room rates from CRM
        $roomTypes = $this->roomRepository->findRoomTypesByVenueId($venueId);
        $rates = [];

        foreach ($roomTypes as $roomType) {
            $rates[] = [
                'room_type_code' => $roomType->getExternalCode(),
                'base_rate' => $roomType->getBaseRate(),
                'currency' => $roomType->getCurrency(),
            ];
        }

        // Push to marketplace API
        Log::channel('marketplace-sync')->info('Rates sync initiated', [
            'venue_id' => $venueId,
            'marketplace' => $marketplace,
            'rates_count' => count($rates),
        ]);
    }

    private function findOrCreateGuest(MarketplaceBookingDTO $dto): Guest
    {
        // Try to find existing guest by email or phone
        $guest = $this->guestRepository->findByEmail($dto->guestEmail);

        if (!$guest) {
            $guest = $this->guestRepository->findByPhone($dto->guestPhone);
        }

        if (!$guest) {
            // Create new guest
            $guest = $this->guestRepository->create([
                'tenant_id' => $dto->tenantId,
                'first_name' => $dto->guestFirstName,
                'last_name' => $dto->guestLastName,
                'email' => $dto->guestEmail,
                'phone' => $dto->guestPhone,
                'nationality' => $dto->guestNationality ?? null,
                'language' => $dto->guestLanguage ?? 'ru',
                'total_stays' => 0,
                'total_nights' => 0,
                'total_spent' => 0.0,
            ]);
        }

        return $guest;
    }

    private function mapStatus(string $source, string $marketplaceStatus): string
    {
        $statusMap = [
            'booking_com' => [
                'new' => 'pending',
                'confirmed' => 'prepaid',
                'cancelled' => 'cancelled',
                'no_show' => 'no_show',
                'modified' => 'pending',
            ],
            'ostrovok' => [
                'new' => 'pending',
                'confirmed' => 'prepaid',
                'cancelled' => 'cancelled',
                'completed' => 'checked_out',
            ],
            'airbnb' => [
                'pending' => 'pending',
                'accepted' => 'prepaid',
                'confirmed' => 'paid',
                'cancelled' => 'cancelled',
                'completed' => 'checked_out',
            ],
        ];

        return $statusMap[$source][$marketplaceStatus] ?? 'pending';
    }

    private function mapPaymentStatus(string $marketplacePaymentStatus): string
    {
        return match ($marketplacePaymentStatus) {
            'paid', 'confirmed', 'completed' => 'paid',
            'partial' => 'partial',
            'pending' => 'pending',
            'failed' => 'failed',
            default => 'pending',
        };
    }
}
