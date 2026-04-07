<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Services;

use App\Domains\Hotels\Models\Booking;
use App\Domains\Hotels\Models\Hotel;
use App\Domains\Hotels\Models\Room;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;

/**
 * Сервис управления объектами размещения (отели, номера).
 * Layer 3: Services — CatVRF 2026
 *
 * CRUD-операции для Hotel Property, Room, Booking.
 * FraudCheck + DB::transaction + AuditService + correlation_id.
 *
 * @package App\Domains\Hotels\Services
 */
final readonly class HotelPropertyService
{
    public function __construct(
        private FraudControlService $fraud,
        private AuditService $audit,
        private DatabaseManager $db,
        private LoggerInterface $logger,
        private Guard $guard,
    ) {}

    /**
     * Создать объект размещения (отель).
     *
     * @param array<string, mixed> $data
     */
    public function createProperty(array $data, int $tenantId, string $correlationId): Hotel
    {
        $userId = (int) $this->guard->id();

        $this->fraud->check(
            userId: $userId,
            operationType: 'hotel_property_create',
            amount: 0,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($data, $tenantId, $correlationId) {
            $hotel = Hotel::create([
                'tenant_id'      => $tenantId,
                'name'           => $data['name'],
                'address'        => $data['address'],
                'geo_point'      => $data['geo_point'] ?? null,
                'stars'          => $data['stars'] ?? 3,
                'type'           => $data['type'] ?? 'hotel',
                'is_active'      => true,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log(
                action: 'hotel_property_created',
                subjectType: Hotel::class,
                subjectId: $hotel->id,
                old: [],
                new: $hotel->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Hotel property created', [
                'hotel_id'       => $hotel->id,
                'tenant_id'      => $tenantId,
                'correlation_id' => $correlationId,
            ]);

            return $hotel;
        });
    }

    /**
     * Создать номер в объекте размещения.
     *
     * @param array<string, mixed> $data
     */
    public function createRoom(array $data, int $propertyId, string $correlationId): Room
    {
        $userId = (int) $this->guard->id();

        $this->fraud->check(
            userId: $userId,
            operationType: 'hotel_room_create',
            amount: 0,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($data, $propertyId, $correlationId) {
            $room = Room::create([
                'property_id'     => $propertyId,
                'room_number'     => $data['room_number'],
                'type'            => $data['type'],
                'capacity'        => $data['capacity'],
                'price_per_night' => $data['price_per_night'],
                'is_active'       => true,
                'correlation_id'  => $correlationId,
            ]);

            $this->audit->log(
                action: 'hotel_room_created',
                subjectType: Room::class,
                subjectId: $room->id,
                old: [],
                new: $room->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Hotel room created', [
                'room_id'        => $room->id,
                'property_id'    => $propertyId,
                'correlation_id' => $correlationId,
            ]);

            return $room;
        });
    }

    /**
     * Создать бронирование для объекта размещения.
     *
     * @param array<string, mixed> $data
     */
    public function createBooking(array $data, int $propertyId, int $userId, string $correlationId): Booking
    {
        $this->fraud->check(
            userId: $userId,
            operationType: 'hotel_booking_create',
            amount: (int) ($data['total_price'] ?? 0),
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($data, $propertyId, $userId, $correlationId) {
            $booking = Booking::create([
                'property_id'    => $propertyId,
                'user_id'        => $userId,
                'check_in'       => $data['check_in'],
                'check_out'      => $data['check_out'],
                'total_price'    => $data['total_price'],
                'status'         => 'pending',
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log(
                action: 'hotel_booking_created',
                subjectType: Booking::class,
                subjectId: $booking->id,
                old: [],
                new: $booking->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Hotel booking created', [
                'booking_id'     => $booking->id,
                'property_id'    => $propertyId,
                'user_id'        => $userId,
                'correlation_id' => $correlationId,
            ]);

            return $booking;
        });
    }

    /**
     * Получить статистику объекта размещения.
     *
     * @return array<string, mixed>
     */
    public function getPropertyStats(Hotel $property, int $tenantId, string $correlationId): array
    {
        $totalBookings = Booking::query()
            ->where('property_id', $property->id)
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->count();

        $totalRevenue = Booking::query()
            ->where('property_id', $property->id)
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->sum('total_price');

        $this->logger->info('Hotel property stats fetched', [
            'hotel_id'       => $property->id,
            'tenant_id'      => $tenantId,
            'correlation_id' => $correlationId,
        ]);

        return [
            'total_bookings' => $totalBookings,
            'total_revenue'  => (int) $totalRevenue,
            'rating'         => $property->rating ?? 0,
        ];
    }
}
