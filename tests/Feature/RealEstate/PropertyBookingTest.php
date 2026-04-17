<?php declare(strict_types=1);

namespace Tests\Feature\RealEstate;

use Tests\TestCase;
use Modules\RealEstate\Models\Property;
use Modules\RealEstate\Models\PropertyBooking;
use Modules\RealEstate\Services\PropertyBookingService;
use Modules\RealEstate\Enums\BookingStatus;
use Modules\RealEstate\Enums\PropertyStatus;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class PropertyBookingTest extends TestCase
{
    use RefreshDatabase;

    private PropertyBookingService $bookingService;

    private Property $property;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bookingService = app(PropertyBookingService::class);

        $this->property = Property::create([
            'tenant_id' => 1,
            'uuid' => Str::uuid()->toString(),
            'correlation_id' => Str::uuid()->toString(),
            'owner_id' => 1,
            'title' => 'Test Property',
            'description' => 'Test Description',
            'address' => 'Test Address',
            'city' => 'Москва',
            'region' => 'Moscow',
            'lat' => 55.7558,
            'lon' => 37.6173,
            'property_type' => 'apartment',
            'status' => PropertyStatus::AVAILABLE,
            'price' => 10000000,
            'area' => 100,
            'rooms' => 3,
            'floor' => 5,
            'total_floors' => 10,
            'year_built' => 2020,
            'features' => ['parking' => true, 'elevator' => true],
            'images' => [],
            'tags' => ['test'],
        ]);

        $this->user = User::factory()->create(['tenant_id' => 1]);
    }

    public function test_can_create_booking(): void
    {
        $data = [
            'property_id' => $this->property->id,
            'user_id' => $this->user->id,
            'viewing_slot' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'amount' => 10000000,
            'wallet_id' => 1,
            'use_escrow' => true,
            'correlation_id' => Str::uuid()->toString(),
        ];

        $booking = $this->bookingService->createBooking($data);

        $this->assertInstanceOf(PropertyBooking::class, $booking);
        $this->assertEquals($this->property->id, $booking->property_id);
        $this->assertEquals($this->user->id, $booking->user_id);
        $this->assertEquals(BookingStatus::PENDING, $booking->status);
        $this->assertFalse($booking->isHoldExpired());
        $this->assertDatabaseHas('real_estate_bookings', [
            'id' => $booking->id,
            'property_id' => $this->property->id,
        ]);
    }

    public function test_can_confirm_booking(): void
    {
        $booking = PropertyBooking::create([
            'tenant_id' => 1,
            'uuid' => Str::uuid()->toString(),
            'correlation_id' => Str::uuid()->toString(),
            'property_id' => $this->property->id,
            'user_id' => $this->user->id,
            'viewing_slot' => now()->addDays(2),
            'amount' => 10000000,
            'status' => BookingStatus::PENDING,
            'deal_score' => ['overall' => 0.8, 'credit' => 0.7, 'legal' => 0.9, 'liquidity' => 0.8, 'recommended' => true],
            'fraud_score' => 0.1,
            'is_b2b' => false,
            'hold_until' => now()->addMinutes(30),
            'face_id_verified' => true,
            'blockchain_verified' => false,
        ]);

        $confirmedBooking = $this->bookingService->confirmBooking($booking->id, Str::uuid()->toString());

        $this->assertEquals(BookingStatus::CONFIRMED, $confirmedBooking->status);
        $this->assertTrue($confirmedBooking->blockchain_verified);
        $this->assertNotNull($confirmedBooking->webrtc_room_id);
    }

    public function test_can_complete_deal(): void
    {
        $booking = PropertyBooking::create([
            'tenant_id' => 1,
            'uuid' => Str::uuid()->toString(),
            'correlation_id' => Str::uuid()->toString(),
            'property_id' => $this->property->id,
            'user_id' => $this->user->id,
            'viewing_slot' => now()->addDays(2),
            'amount' => 10000000,
            'status' => BookingStatus::CONFIRMED,
            'deal_score' => ['overall' => 0.8, 'credit' => 0.7, 'legal' => 0.9, 'liquidity' => 0.8, 'recommended' => true],
            'fraud_score' => 0.1,
            'is_b2b' => false,
            'face_id_verified' => true,
            'blockchain_verified' => true,
            'metadata' => ['wallet_id' => 1],
        ]);

        $completedBooking = $this->bookingService->completeDeal($booking->id, Str::uuid()->toString());

        $this->assertEquals(BookingStatus::COMPLETED, $completedBooking->status);
        $this->assertEquals(PropertyStatus::SOLD, $this->property->fresh()->status);
    }

    public function test_can_cancel_booking(): void
    {
        $booking = PropertyBooking::create([
            'tenant_id' => 1,
            'uuid' => Str::uuid()->toString(),
            'correlation_id' => Str::uuid()->toString(),
            'property_id' => $this->property->id,
            'user_id' => $this->user->id,
            'viewing_slot' => now()->addDays(2),
            'amount' => 10000000,
            'status' => BookingStatus::PENDING,
            'deal_score' => ['overall' => 0.8, 'credit' => 0.7, 'legal' => 0.9, 'liquidity' => 0.8, 'recommended' => true],
            'fraud_score' => 0.1,
            'is_b2b' => false,
            'hold_until' => now()->addMinutes(30),
            'face_id_verified' => true,
            'blockchain_verified' => false,
            'metadata' => ['wallet_id' => 1],
        ]);

        $cancelledBooking = $this->bookingService->cancelBooking($booking->id, 'User cancelled', Str::uuid()->toString());

        $this->assertEquals(BookingStatus::CANCELLED, $cancelledBooking->status);
        $this->assertEquals('User cancelled', $cancelledBooking->metadata['cancellation_reason']);
    }

    public function test_b2b_booking_has_commission_split(): void
    {
        $data = [
            'property_id' => $this->property->id,
            'user_id' => $this->user->id,
            'viewing_slot' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'amount' => 10000000,
            'business_group_id' => 1,
            'inn' => '1234567890',
            'correlation_id' => Str::uuid()->toString(),
        ];

        $booking = $this->bookingService->createBooking($data);

        $this->assertTrue($booking->is_b2b);
        $this->assertNotNull($booking->commission_split);
        $this->assertArrayHasKey('platform', $booking->commission_split);
        $this->assertArrayHasKey('agent', $booking->commission_split);
        $this->assertArrayHasKey('referral', $booking->commission_split);
    }

    public function test_high_fraud_score_blocks_booking(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Booking flagged as high risk');

        $data = [
            'property_id' => $this->property->id,
            'user_id' => $this->user->id,
            'viewing_slot' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'amount' => 10000000,
            'correlation_id' => Str::uuid()->toString(),
        ];

        $booking = $this->bookingService->createBooking($data);

        $this->fail('Expected DomainException was not thrown');
    }

    public function test_expired_booking_cannot_be_confirmed(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Booking cannot be confirmed');

        $booking = PropertyBooking::create([
            'tenant_id' => 1,
            'uuid' => Str::uuid()->toString(),
            'correlation_id' => Str::uuid()->toString(),
            'property_id' => $this->property->id,
            'user_id' => $this->user->id,
            'viewing_slot' => now()->addDays(2),
            'amount' => 10000000,
            'status' => BookingStatus::PENDING,
            'deal_score' => ['overall' => 0.8, 'credit' => 0.7, 'legal' => 0.9, 'liquidity' => 0.8, 'recommended' => true],
            'fraud_score' => 0.1,
            'is_b2b' => false,
            'hold_until' => now()->subMinutes(10),
            'face_id_verified' => true,
            'blockchain_verified' => false,
        ]);

        $this->bookingService->confirmBooking($booking->id, Str::uuid()->toString());
    }

    public function test_can_initiate_video_call(): void
    {
        $booking = PropertyBooking::create([
            'tenant_id' => 1,
            'uuid' => Str::uuid()->toString(),
            'correlation_id' => Str::uuid()->toString(),
            'property_id' => $this->property->id,
            'user_id' => $this->user->id,
            'viewing_slot' => now()->addDays(2),
            'amount' => 10000000,
            'status' => BookingStatus::CONFIRMED,
            'deal_score' => ['overall' => 0.8, 'credit' => 0.7, 'legal' => 0.9, 'liquidity' => 0.8, 'recommended' => true],
            'fraud_score' => 0.1,
            'is_b2b' => false,
            'face_id_verified' => true,
            'blockchain_verified' => true,
            'webrtc_room_id' => null,
        ]);

        $result = $this->bookingService->initiateVideoCall($booking->id, $this->user->id, Str::uuid()->toString());

        $this->assertArrayHasKey('room_id', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('expires_at', $result);
        $this->assertArrayHasKey('participants', $result);
    }

    public function test_can_get_available_slots(): void
    {
        $startDate = now()->addDay()->format('Y-m-d');
        $endDate = now()->addDays(7)->format('Y-m-d');

        $slots = $this->bookingService->getAvailableSlots($this->property->id, $startDate, $endDate, Str::uuid()->toString());

        $this->assertIsArray($slots);
        $this->assertGreaterThan(0, count($slots));
        foreach ($slots as $slot) {
            $this->assertArrayHasKey('datetime', $slot);
            $this->assertArrayHasKey('is_peak', $slot);
            $this->assertArrayHasKey('demand_multiplier', $slot);
            $this->assertArrayHasKey('price_adjustment', $slot);
        }
    }
}
