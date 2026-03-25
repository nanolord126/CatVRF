<?php declare(strict_types=1);

namespace Modules\Hotels\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Hotels\Models\HotelProperty;
use Modules\Hotels\Models\Room;
use Modules\Hotels\Models\Booking;
use App\Services\FraudControlService;

/**
 * Hotel Property Management Service
 * CANON 2026 - Production Ready
 */
final class HotelPropertyService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function createProperty(array $data, int $tenantId, string $correlationId): HotelProperty
    {


        $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
        );
$this->db->transaction(function () use ($data, $tenantId, $correlationId) {
            $this->log->channel('audit')->info('Creating hotel property', [
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
            ]);

            return HotelProperty::create([
                'tenant_id' => $tenantId,
                'name' => $data['name'],
                'address' => $data['address'],
                'geo_point' => $data['geo_point'] ?? null,
                'stars' => $data['stars'] ?? 3,
                'type' => $data['type'] ?? 'hotel',
                'is_active' => true,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    public function createRoom(array $data, int $propertyId, string $correlationId): Room
    {


        $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
        );
$this->db->transaction(function () use ($data, $propertyId, $correlationId) {
            return Room::create([
                'property_id' => $propertyId,
                'room_number' => $data['room_number'],
                'type' => $data['type'],
                'capacity' => $data['capacity'],
                'price_per_night' => $data['price_per_night'],
                'is_active' => true,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    public function createBooking(array $data, int $propertyId, int $userId, string $correlationId): Booking
    {


        $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
        );
$this->db->transaction(function () use ($data, $propertyId, $userId, $correlationId) {
            $this->log->channel('audit')->info('Creating hotel booking', [
                'correlation_id' => $correlationId,
                'property_id' => $propertyId,
                'user_id' => $userId,
            ]);

            return Booking::create([
                'property_id' => $propertyId,
                'user_id' => $userId,
                'check_in' => $data['check_in'],
                'check_out' => $data['check_out'],
                'total_price' => $data['total_price'],
                'status' => 'pending',
                'correlation_id' => $correlationId,
            ]);
        });
    }

    public function getPropertyStats(HotelProperty $property): array
    {


        $totalBookings = Booking::query()
            ->where('property_id', $property->id)
            ->where('status', 'completed')
            ->count();

        $totalRevenue = Booking::query()
            ->where('property_id', $property->id)
            ->where('status', 'completed')
            ->sum('total_price');

        return [
            'total_bookings' => $totalBookings,
            'total_revenue' => $totalRevenue,
            'rating' => $property->rating ?? 0,
        ];
    }
}
