<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Hotels;

use App\Domains\Hotels\Services\HotelPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class HotelPricingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculate_room_price(): void
    {
        $service = app(HotelPricingService::class);
        $price = $service->calculateRoomPrice(
            hotelId: 1,
            roomId: 1,
            checkIn: now()->addDay(),
            checkOut: now()->addDays(3),
            guestCount: 2,
            tenantId: 1,
            isB2B: false,
            correlationId: 'test-correlation',
        );

        $this->assertGreaterThan(0, $price);
    }

    public function test_apply_seasonal_pricing(): void
    {
        $service = app(HotelPricingService::class);
        $seasonalPrice = $service->applySeasonalPricing(
            basePrice: 5000,
            checkIn: now()->addDay(),
            tenantId: 1,
            correlationId: 'test-correlation',
        );

        $this->assertGreaterThan(0, $seasonalPrice);
    }
}
