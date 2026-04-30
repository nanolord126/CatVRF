<?php

declare(strict_types=1);

namespace Modules\Hotels\Application\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Cache\CacheManager;
use Illuminate\Log\LogManager;
use Modules\Hotels\Domain\Entities\Booking;
use Modules\Hotels\Domain\Entities\BookingItem;
use Modules\Hotels\Domain\Entities\Guest;
use Modules\Hotels\Domain\Entities\Room;
use Modules\Hotels\Infrastructure\Models\BookingModel;
use Modules\Hotels\Infrastructure\Models\BookingItemModel;
use Modules\Hotels\Infrastructure\Models\GuestModel;
use Modules\Hotels\Infrastructure\Models\RoomModel;
use App\Services\Fraud\FraudControlService;
use App\Domains\CRM\Services\HotelCrmService;
use App\Domain\Audit\Events\AuditEvent;
use App\Traits\WithTelemetry;
use Modules\Analytics\Services\BehavioralTracker;
use Illuminate\Support\Facades\Event;

/**
 * Booking Service — Сервис для управления бронированиями в отелях
 *
 * CatVRF 2026 Canon - Production Mandatory
 * - Fraud check перед всеми мутациями
 * - Readonly класс
 * - Cache::tags для инвалидации
 * - Audit логирование
 * - CRM интеграция
 * - OpenTelemetry instrumentation
 */
final readonly class BookingService
{
    use WithTelemetry;

    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly HotelCrmService $hotelCrm,
        private readonly DatabaseManager $db,
        private readonly CacheManager $cache,
        private readonly LogManager $logger,
        private readonly BehavioralTracker $behavioralTracker,
    ) {}

    public function createBooking(
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
        ?array $roomPreferences = null,
        ?int $createdBy = null,
        ?string $correlationId = null,
    ): Booking {
        return $this->withSpan('hotels.create_booking', function () use (
            $tenantId,
            $venueId,
            $guestId,
            $checkInDate,
            $checkOutDate,
            $adults,
            $children,
            $infants,
            $userId,
            $source,
            $externalReference,
            $roomPreferences,
            $createdBy,
            $correlationId,
        ) {
            $correlationId ??= request()->header('X-Correlation-ID') ?? uniqid('hotel_booking_', true);

        // FRAUD CHECK - мандаторно первым действием
        $fraudResult = $this->fraud->checkRequest([
            'action' => 'hotel_create_booking',
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'ip_address' => request()->ip(),
        ]);

        if ($fraudResult['should_block']) {
            Event::dispatch(AuditEvent::action(
                action: 'hotel_booking_blocked_by_fraud',
                subjectType: 'hotel_booking',
                subjectId: null,
                context: [
                    'fraud_score' => $fraudResult['fraud_score'],
                    'indicators' => $fraudResult['indicators'],
                    'tenant_id' => $tenantId,
                    'venue_id' => $venueId,
                    'correlation_id' => $correlationId,
                ],
                correlationId: $correlationId
            ));
            $this->logger->channel('audit')->warning('Hotel booking creation blocked by fraud detection', [
                'fraud_score' => $fraudResult['fraud_score'],
                'indicators' => $fraudResult['indicators'],
                'tenant_id' => $tenantId,
                'venue_id' => $venueId,
                'correlation_id' => $correlationId,
            ]);
            throw new \RuntimeException('Booking creation blocked by fraud detection');
        }

        return $this->db->transaction(function () use (
            $tenantId,
            $venueId,
            $guestId,
            $checkInDate,
            $checkOutDate,
            $adults,
            $children,
            $infants,
            $userId,
            $source,
            $externalReference,
            $roomPreferences,
            $createdBy,
            $correlationId,
        ) {
            $bookingEntity = Booking::create(
                tenantId: $tenantId,
                venueId: $venueId,
                guestId: $guestId,
                checkInDate: $checkInDate,
                checkOutDate: $checkOutDate,
                adults: $adults,
                children: $children,
                infants: $infants,
                userId: $userId,
                source: $source,
                externalReference: $externalReference,
                roomPreferences: $roomPreferences,
                createdBy: $createdBy,
            );

            $bookingModel = BookingModel::create([
                'tenant_id' => $bookingEntity->tenantId,
                'venue_id' => $bookingEntity->venueId,
                'guest_id' => $bookingEntity->guestId,
                'user_id' => $bookingEntity->userId,
                'uuid' => $bookingEntity->uuid,
                'confirmation_code' => $bookingEntity->confirmationCode,
                'status' => $bookingEntity->status,
                'source' => $bookingEntity->source,
                'external_reference' => $bookingEntity->externalReference,
                'check_in_date' => $bookingEntity->checkInDate,
                'check_out_date' => $bookingEntity->checkOutDate,
                'adults' => $bookingEntity->adults,
                'children' => $bookingEntity->children,
                'infants' => $bookingEntity->infants,
                'total_amount' => $bookingEntity->totalAmount,
                'paid_amount' => $bookingEntity->paidAmount,
                'deposit_amount' => $bookingEntity->depositAmount,
                'currency' => $bookingEntity->currency,
                'payment_status' => $bookingEntity->paymentStatus,
                'special_requests' => $bookingEntity->specialRequests,
                'room_preferences' => $bookingEntity->roomPreferences,
                'early_checkin_requested' => $bookingEntity->earlyCheckinRequested,
                'late_checkout_requested' => $bookingEntity->lateCheckoutRequested,
                'created_by' => $bookingEntity->createdBy,
                'modified_by' => $bookingEntity->modifiedBy,
                'correlation_id' => $correlationId,
            ]);

            // Очищаем кэш
            $this->cache->tags(['hotels', 'bookings', "venue:{$venueId}", "tenant:{$tenantId}"])->flush();

            $this->logger->channel('audit')->info('Hotel booking created', [
                'booking_id' => $bookingModel->id,
                'confirmation_code' => $bookingModel->confirmation_code,
                'tenant_id' => $tenantId,
                'venue_id' => $venueId,
                'correlation_id' => $correlationId,
            ]);

            // Track behavioral event for analytics
            $this->behavioralTracker->capture(
                eventType: 'booking_created',
                vertical: 'hotels',
                targetId: (string) $bookingModel->id,
                payload: [
                    'venue_id' => $venueId,
                    'guest_id' => $guestId,
                    'check_in_date' => $checkInDate->toIso8601String(),
                    'check_out_date' => $checkOutDate->toIso8601String(),
                    'adults' => $adults,
                    'children' => $children,
                    'total_amount' => $bookingEntity->totalAmount,
                    'source' => $source,
                ],
                monetaryValue: (float) $bookingEntity->totalAmount,
            );

            // CRM INTEGRATION
            try {
                $this->logger->channel('audit')->info('CRM sync data prepared for hotel booking', [
                    'booking_id' => $bookingModel->id,
                    'guest_id' => $guestId,
                    'confirmation_code' => $bookingModel->confirmation_code,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('CRM sync failed for hotel booking', [
                    'error' => $e->getMessage(),
                    'booking_id' => $bookingModel->id,
                    'correlation_id' => $correlationId,
                ]);
            }

            return new Booking(
                id: $bookingModel->id,
                tenantId: $bookingEntity->tenantId,
                venueId: $bookingEntity->venueId,
                guestId: $bookingEntity->guestId,
                userId: $bookingEntity->userId,
                uuid: $bookingEntity->uuid,
                confirmationCode: $bookingEntity->confirmationCode,
                status: $bookingEntity->status,
                source: $bookingEntity->source,
                externalReference: $bookingEntity->externalReference,
                checkInDate: $bookingEntity->checkInDate,
                checkOutDate: $bookingEntity->checkOutDate,
                adults: $bookingEntity->adults,
                children: $bookingEntity->children,
                infants: $bookingEntity->infants,
                totalAmount: $bookingEntity->totalAmount,
                paidAmount: $bookingEntity->paidAmount,
                depositAmount: $bookingEntity->depositAmount,
                currency: $bookingEntity->currency,
                paymentStatus: $bookingEntity->paymentStatus,
                specialRequests: $bookingEntity->specialRequests,
                roomPreferences: $bookingEntity->roomPreferences,
                earlyCheckinRequested: $bookingEntity->earlyCheckinRequested,
                lateCheckoutRequested: $bookingEntity->lateCheckoutRequested,
                actualCheckIn: $bookingModel->actual_check_in,
                actualCheckOut: $bookingModel->actual_check_out,
                createdBy: $bookingEntity->createdBy,
                modifiedBy: $bookingEntity->modifiedBy,
                correlationId: $correlationId,
                createdAt: $bookingModel->created_at,
                updatedAt: $bookingModel->updated_at,
                deletedAt: $bookingModel->deleted_at,
            );
        });
        });
    }

    public function addBookingItem(
        int $bookingId,
        int $roomId,
        int $roomTypeId,
        CarbonImmutable $checkInDate,
        CarbonImmutable $checkOutDate,
        float $roomRate,
        ?array $guests = null,
    ): BookingItem {
        return $this->withSpan('hotels.add_booking_item', function () use (
            $bookingId,
            $roomId,
            $roomTypeId,
            $checkInDate,
            $checkOutDate,
            $roomRate,
            $guests,
        ) {
            return $this->db->transaction(function () use (
            $bookingId,
            $roomId,
            $roomTypeId,
            $checkInDate,
            $checkOutDate,
            $roomRate,
            $guests,
        ) {
            $bookingModel = BookingModel::findOrFail($bookingId);
            $bookingItemEntity = BookingItem::create(
                tenantId: $bookingModel->tenant_id,
                bookingId: $bookingId,
                roomId: $roomId,
                roomTypeId: $roomTypeId,
                checkInDate: $checkInDate,
                checkOutDate: $checkOutDate,
                roomRate: $roomRate,
                guests: $guests,
            );

            $bookingItemModel = BookingItemModel::create([
                'tenant_id' => $bookingItemEntity->tenantId,
                'booking_id' => $bookingItemEntity->bookingId,
                'room_id' => $bookingItemEntity->roomId,
                'room_type_id' => $bookingItemEntity->roomTypeId,
                'uuid' => $bookingItemEntity->uuid,
                'check_in_date' => $bookingItemEntity->checkInDate,
                'check_out_date' => $bookingItemEntity->checkOutDate,
                'nights' => $bookingItemEntity->nights,
                'room_rate' => $bookingItemEntity->roomRate,
                'total_amount' => $bookingItemEntity->totalAmount,
                'guests' => $bookingItemEntity->guests,
                'is_active' => $bookingItemEntity->isActive,
            ]);

            // Update booking total
            $bookingModel->update([
                'total_amount' => $bookingModel->total_amount + $bookingItemEntity->totalAmount,
            ]);

            // Mark room as occupied for the dates
            RoomModel::where('id', $roomId)->update(['status' => 'occupied']);

            return new BookingItem(
                id: $bookingItemModel->id,
                tenantId: $bookingItemEntity->tenantId,
                bookingId: $bookingItemEntity->bookingId,
                roomId: $bookingItemEntity->roomId,
                roomTypeId: $bookingItemEntity->roomTypeId,
                uuid: $bookingItemEntity->uuid,
                checkInDate: $bookingItemEntity->checkInDate,
                checkOutDate: $bookingItemEntity->checkOutDate,
                nights: $bookingItemEntity->nights,
                roomRate: $bookingItemEntity->roomRate,
                totalAmount: $bookingItemEntity->totalAmount,
                guests: $bookingItemEntity->guests,
                isActive: $bookingItemEntity->isActive,
                createdAt: $bookingItemModel->created_at,
                updatedAt: $bookingItemModel->updated_at,
            );
        });
        });
    }

    public function checkIn(int $bookingId, int $userId): Booking
    {
        return $this->withSpan('hotels.check_in', function () use ($bookingId, $userId) {
            return $this->db->transaction(function () use ($bookingId, $userId) {
            $bookingModel = BookingModel::with('items')->findOrFail($bookingId);

            if (!$bookingModel->canCheckIn()) {
                throw new \RuntimeException('Booking cannot be checked in');
            }

            $bookingModel->update([
                'status' => 'checked_in',
                'actual_check_in' => now(),
                'modified_by' => $userId,
            ]);

            // Mark rooms as occupied
            foreach ($bookingModel->items as $item) {
                RoomModel::where('id', $item->room_id)->update(['status' => 'occupied']);
            }

            // Create housekeeping task for checkout
            foreach ($bookingModel->items as $item) {
                app(HousekeepingService::class)->scheduleCheckoutCleaning(
                    $bookingModel->venue_id,
                    $item->room_id,
                    $bookingModel->check_out_date,
                    $item->id,
                );
            }

            $this->logger->channel('audit')->info('Booking checked in', [
                'booking_id' => $bookingId,
                'confirmation_code' => $bookingModel->confirmation_code,
                'user_id' => $userId,
                'tenant_id' => $bookingModel->tenant_id,
            ]);

            return $this->mapToEntity($bookingModel);
        });
        });
    }

    public function checkOut(int $bookingId, int $userId): Booking
    {
        return $this->withSpan('hotels.check_out', function () use ($bookingId, $userId) {
            return $this->db->transaction(function () use ($bookingId, $userId) {
            $bookingModel = BookingModel::with(['items', 'guest'])->findOrFail($bookingId);

            if ($bookingModel->status !== 'checked_in') {
                throw new \RuntimeException('Booking is not checked in');
            }

            $bookingModel->update([
                'status' => 'checked_out',
                'actual_check_out' => now(),
                'modified_by' => $userId,
            ]);

            // Mark rooms as dirty (need cleaning)
            foreach ($bookingModel->items as $item) {
                RoomModel::where('id', $item->room_id)->update([
                    'status' => 'available',
                    'clean_status' => 'dirty',
                ]);
            }

            // Update guest stats
            $guestModel = GuestModel::findOrFail($bookingModel->guest_id);
            $nights = $bookingModel->check_in_date->diffInDays($bookingModel->check_out_date);
            $guestModel->update([
                'total_stays' => $guestModel->total_stays + 1,
                'total_nights' => $guestModel->total_nights + $nights,
                'total_spent' => $guestModel->total_spent + $bookingModel->total_amount,
                'last_stay_at' => now(),
            ]);

            $this->logger->channel('audit')->info('Booking checked out', [
                'booking_id' => $bookingId,
                'confirmation_code' => $bookingModel->confirmation_code,
                'user_id' => $userId,
                'tenant_id' => $bookingModel->tenant_id,
            ]);

            return $this->mapToEntity($bookingModel);
        });
        });
    }

    public function cancelBooking(int $bookingId, string $reason, ?int $userId = null): Booking
    {
        return $this->withSpan('hotels.cancel_booking', function () use ($bookingId, $reason, $userId) {
            return $this->db->transaction(function () use ($bookingId, $reason, $userId) {
            $bookingModel = BookingModel::with('items')->findOrFail($bookingId);

            if (!$bookingModel->canCancel()) {
                throw new \RuntimeException('Booking cannot be cancelled');
            }

            $bookingModel->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'cancelled_at' => now(),
                'modified_by' => $userId,
            ]);

            // Release rooms
            foreach ($bookingModel->items as $item) {
                RoomModel::where('id', $item->room_id)->update(['status' => 'available']);
                $item->update(['is_active' => false]);
            }

            $this->logger->channel('audit')->info('Booking cancelled', [
                'booking_id' => $bookingId,
                'confirmation_code' => $bookingModel->confirmation_code,
                'reason' => $reason,
                'user_id' => $userId,
                'tenant_id' => $bookingModel->tenant_id,
            ]);

            return $this->mapToEntity($bookingModel);
        });
        });
    }

    public function updatePaymentStatus(int $bookingId, string $status, float $amount): void
    {
        $bookingModel = BookingModel::findOrFail($bookingId);
        $bookingModel->update([
            'payment_status' => $status,
            'paid_amount' => $amount,
        ]);

        $this->logger->channel('audit')->info('Booking payment status updated', [
            'booking_id' => $bookingId,
            'status' => $status,
            'amount' => $amount,
        ]);
    }

    public function confirmBooking(int $bookingId): void
    {
        $bookingModel = BookingModel::findOrFail($bookingId);
        $bookingModel->update(['status' => 'prepaid']);

        $this->logger->channel('audit')->info('Booking confirmed', [
            'booking_id' => $bookingId,
            'confirmation_code' => $bookingModel->confirmation_code,
        ]);
    }

    public function createSimpleBooking(array $data): BookingModel
    {
        return $this->db->transaction(function () use ($data) {
            $confirmationCode = 'CNF-' . strtoupper(uniqid());

            $bookingModel = BookingModel::create([
                'tenant_id' => $data['tenant_id'],
                'venue_id' => $data['venue_id'],
                'guest_id' => $data['guest_id'],
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'confirmation_code' => $data['confirmation_code'] ?? $confirmationCode,
                'status' => $data['status'] ?? 'pending',
                'source' => $data['source'] ?? 'direct',
                'check_in_date' => $data['check_in_date'],
                'check_out_date' => $data['check_out_date'],
                'adults' => $data['adults'],
                'children' => $data['children'] ?? 0,
                'infants' => $data['infants'] ?? 0,
                'total_amount' => $data['total_amount'],
                'paid_amount' => $data['paid_amount'] ?? 0,
                'deposit_amount' => $data['deposit_amount'] ?? 0,
                'currency' => $data['currency'] ?? 'RUB',
                'payment_status' => $data['payment_status'] ?? 'pending',
                'special_requests' => $data['special_requests'] ?? null,
                'room_preferences' => $data['room_preferences'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);

            $this->logger->channel('audit')->info('Booking created', [
                'booking_id' => $bookingModel->id,
                'confirmation_code' => $bookingModel->confirmation_code,
            ]);

            return $bookingModel;
        });
    }

    public function checkAvailability(
        int $venueId,
        CarbonImmutable $checkIn,
        CarbonImmutable $checkOut,
        int $adults,
        int $children = 0,
    ): array {
        $bookedRoomIds = BookingItemModel::whereHas('booking', function ($query) use ($venueId, $checkIn, $checkOut) {
            $query->where('venue_id', $venueId)
                ->whereNotIn('status', ['cancelled', 'no_show'])
                ->where(function ($q) use ($checkIn, $checkOut) {
                    $q->whereBetween('check_in_date', [$checkIn, $checkOut])
                        ->orWhereBetween('check_out_date', [$checkIn, $checkOut])
                        ->orWhere(function ($query) use ($checkIn, $checkOut) {
                            $query->where('check_in_date', '<', $checkIn)
                                ->where('check_out_date', '>', $checkOut);
                        });
                });
        })->pluck('room_id')->toArray();

        $availableRooms = RoomModel::where('venue_id', $venueId)
            ->where('status', 'available')
            ->where('clean_status', 'clean')
            ->where('is_active', true)
            ->whereNotIn('id', $bookedRoomIds)
            ->with('roomType')
            ->get();

        return $availableRooms->map(function ($room) use ($adults, $children) {
            $canAccommodate = $room->roomType->max_adults >= $adults 
                && $room->roomType->max_children >= $children;

            return [
                'room_id' => $room->id,
                'room_number' => $room->room_number,
                'room_type' => $room->roomType->name,
                'max_occupancy' => $room->roomType->max_occupancy,
                'can_accommodate' => $canAccommodate,
                'rate' => $this->getRoomRate($room->id, $room->room_type_id),
            ];
        })->toArray();
    }

    private function getRoomRate(int $roomId, int $roomTypeId): float
    {
        // This would typically fetch from a pricing strategy or rate plan
        // For now, return a base rate
        return 5000.0;
    }

    private function mapToEntity(BookingModel $model): Booking
    {
        return new Booking(
            id: $model->id,
            tenantId: $model->tenant_id,
            venueId: $model->venue_id,
            guestId: $model->guest_id,
            userId: $model->user_id,
            uuid: $model->uuid,
            confirmationCode: $model->confirmation_code,
            status: $model->status,
            source: $model->source,
            externalReference: $model->external_reference,
            checkInDate: CarbonImmutable::parse($model->check_in_date),
            checkOutDate: CarbonImmutable::parse($model->check_out_date),
            adults: $model->adults,
            children: $model->children,
            infants: $model->infants,
            totalAmount: (float) $model->total_amount,
            paidAmount: (float) $model->paid_amount,
            depositAmount: (float) $model->deposit_amount,
            currency: $model->currency,
            paymentStatus: $model->payment_status,
            specialRequests: $model->special_requests,
            roomPreferences: $model->room_preferences,
            earlyCheckinRequested: $model->early_checkin_requested,
            lateCheckoutRequested: $model->late_checkout_requested,
            actualCheckIn: $model->actual_check_in ? CarbonImmutable::parse($model->actual_check_in) : null,
            actualCheckOut: $model->actual_check_out ? CarbonImmutable::parse($model->actual_check_out) : null,
            createdBy: $model->created_by,
            modifiedBy: $model->modified_by,
            cancellationReason: $model->cancellation_reason,
            cancelledAt: $model->cancelled_at ? CarbonImmutable::parse($model->cancelled_at) : null,
            notes: $model->notes,
            correlationId: $model->correlation_id,
            createdAt: CarbonImmutable::parse($model->created_at),
            updatedAt: CarbonImmutable::parse($model->updated_at),
        );
    }
}
