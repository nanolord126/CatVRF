<?php declare(strict_types=1);

namespace Tests\Feature\Domains\Travel;

use App\Domains\Travel\Models\TravelTour;
use App\Domains\Travel\Models\TravelBooking;
use App\Domains\Travel\Services\TourService;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\BaseTestCase;

/**
 * TourServiceTest — Feature-тесты вертикали Путешествия/Туры.
 */
final class TourServiceTest extends BaseTestCase
{
    use RefreshDatabase;

    private TourService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TourService::class);
    }

    public function test_tour_booking_created_successfully(): void
    {
        $tour = TravelTour::factory()->create([
            'tenant_id'   => $this->tenant->id,
            'price'       => 150_000_00,
            'max_persons' => 10,
            'status'      => 'active',
        ]);

        Wallet::factory()->create([
            'tenant_id'       => $this->tenant->id,
            'current_balance' => 500_000_00,
        ]);

        $booking = $this->service->bookTour([
            'tour_id'        => $tour->id,
            'client_id'      => $this->user->id,
            'tenant_id'      => $this->tenant->id,
            'persons'        => 2,
            'correlation_id' => Str::uuid()->toString(),
        ]);

        $this->assertInstanceOf(TravelBooking::class, $booking);
        $this->assertNotNull($booking->uuid);
        $this->assertSame('pending', $booking->status);
    }

    public function test_tour_booking_commission_14_percent(): void
    {
        $tour = TravelTour::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price'     => 100_000_00, // 100 000 руб
        ]);

        Wallet::factory()->create([
            'tenant_id'       => $this->tenant->id,
            'current_balance' => 500_000_00,
        ]);

        $booking = $this->service->bookTour([
            'tour_id'        => $tour->id,
            'client_id'      => $this->user->id,
            'tenant_id'      => $this->tenant->id,
            'persons'        => 1,
            'correlation_id' => Str::uuid()->toString(),
        ]);

        // Commission = 14% of 100000 = 14000
        $expectedCommission = (int)(100_000_00 * 0.14);
        $this->assertSame($expectedCommission, $booking->commission_amount);
    }

    public function test_booking_cancelled_before_departure_refunds_wallet(): void
    {
        $tour = TravelTour::factory()->create([
            'tenant_id'       => $this->tenant->id,
            'price'           => 50_000_00,
            'departure_date'  => now()->addDays(30),
        ]);

        $wallet = Wallet::factory()->create([
            'tenant_id'       => $this->tenant->id,
            'current_balance' => 200_000_00,
        ]);

        $booking = $this->service->bookTour([
            'tour_id'        => $tour->id,
            'client_id'      => $this->user->id,
            'tenant_id'      => $this->tenant->id,
            'persons'        => 1,
            'correlation_id' => Str::uuid()->toString(),
        ]);

        $balanceAfterBooking = $wallet->fresh()->current_balance;

        $this->service->cancelBooking($booking->id, Str::uuid()->toString());

        $booking->refresh();
        $this->assertSame('cancelled', $booking->status);

        // Wallet should be refunded
        $balanceAfterCancel = $wallet->fresh()->current_balance;
        $this->assertGreaterThan($balanceAfterBooking, $balanceAfterCancel);
    }

    public function test_overbooking_prevented(): void
    {
        $tour = TravelTour::factory()->create([
            'tenant_id'   => $this->tenant->id,
            'price'       => 50_000_00,
            'max_persons' => 2,
        ]);

        Wallet::factory()->create([
            'tenant_id'       => $this->tenant->id,
            'current_balance' => 999_999_00,
        ]);

        // Book 2 persons (fills the tour)
        $this->service->bookTour([
            'tour_id'        => $tour->id,
            'client_id'      => $this->user->id,
            'tenant_id'      => $this->tenant->id,
            'persons'        => 2,
            'correlation_id' => Str::uuid()->toString(),
        ]);

        // Attempt overbooking
        $this->expectException(\RuntimeException::class);
        $this->service->bookTour([
            'tour_id'        => $tour->id,
            'client_id'      => $this->user->id,
            'tenant_id'      => $this->tenant->id,
            'persons'        => 1,
            'correlation_id' => Str::uuid()->toString(),
        ]);
    }
}
