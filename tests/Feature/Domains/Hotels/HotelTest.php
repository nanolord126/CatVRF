<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Hotels;

use App\Domains\Hotels\Models\Hotel;
use App\Domains\Hotels\Models\HotelBooking;
use App\Domains\Hotels\Models\HotelRoomType;
use Database\Factories\Hotels\HotelFactory;
use Database\Factories\Hotels\HotelBookingFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

final class HotelTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Тест: создание отеля
     */
    public function test_can_create_hotel(): void
    {
        $hotel = Hotel::factory()->create();

        $this->assertDatabaseHas('hotels', [
            'id' => $hotel->id,
            'name' => $hotel->name,
            'is_open' => $hotel->is_open,
        ]);
    }

    /**
     * Тест: создание бронирования
     */
    public function test_can_create_hotel_booking(): void
    {
        $hotel = Hotel::factory()->create();
        $roomType = HotelRoomType::factory()->for($hotel)->create();
        $booking = HotelBooking::factory()
            ->for($hotel, 'hotel')
            ->for($roomType, 'roomType')
            ->create();

        $this->assertDatabaseHas('hotel_bookings', [
            'id' => $booking->id,
            'hotel_id' => $hotel->id,
            'status' => 'pending',
        ]);
    }

    /**
     * Тест: проверка открытости отеля
     */
    public function test_hotel_is_open(): void
    {
        $openHotel = Hotel::factory()->open()->create();
        $closedHotel = Hotel::factory()->open()->create(['is_open' => false]);

        $this->assertTrue($openHotel->isOpen());
        $this->assertFalse($closedHotel->isOpen());
    }

    /**
     * Тест: люкс отель (5 звёзд)
     */
    public function test_hotel_luxury_rating(): void
    {
        $luxury = Hotel::factory()->luxury()->create();

        $this->assertEquals(5, $luxury->stars);
        $this->assertGreaterThanOrEqual(4.5, $luxury->rating);
    }

    /**
     * Тест: бюджетный отель
     */
    public function test_hotel_budget_rating(): void
    {
        $budget = Hotel::factory()->budget()->create();

        $this->assertLessThanOrEqual(3, $budget->stars);
        $this->assertLessThanOrEqual(4, $budget->rating);
    }

    /**
     * Тест: статусы бронирования
     */
    public function test_hotel_booking_statuses(): void
    {
        $pending = HotelBooking::factory()->pending()->create();
        $confirmed = HotelBooking::factory()->confirmed()->create();
        $completed = HotelBooking::factory()->completed()->create();

        $this->assertTrue($pending->isPending());
        $this->assertTrue($confirmed->isConfirmed());
        $this->assertTrue($completed->isCompleted());
    }

    /**
     * Тест: расчёт ночей
     */
    public function test_booking_calculate_nights(): void
    {
        $booking = HotelBooking::factory()->create([
            'check_in_date' => now(),
            'check_out_date' => now()->addDays(5),
        ]);

        $this->assertEquals(5, $booking->calculateNights());
    }
}
