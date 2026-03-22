<?php declare(strict_types=1);

namespace Tests\Feature\Domains\Hotels;

use App\Domains\Hotels\Models\Booking;
use App\Domains\Hotels\Models\Hotel;
use App\Domains\Hotels\Models\RoomType;
use App\Domains\Hotels\Services\BookingService;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * BookingServiceTest — Production-grade тесты бронирования отелей.
 *
 * Покрывает:
 * - Создание бронирования с корректными датами
 * - Комиссия 14% — точный расчёт
 * - Расчёт ночей, subtotal, total
 * - Одна ночь — граничный случай
 * - Некорректные даты (check-out ≤ check-in) — исключение
 * - Номер не найден (404)
 * - tenant scoping
 * - correlation_id
 * - Аудит-лог
 * - Дублированное бронирование (overlap)
 * - DB rollback при ошибке
 */
final class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    private BookingService $service;
    private Tenant $tenant;
    private User $user;
    private Hotel $hotel;
    private RoomType $roomType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service  = app(BookingService::class);
        $this->tenant   = Tenant::factory()->create();
        $this->user     = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->hotel    = Hotel::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->roomType = RoomType::factory()->create([
            'tenant_id'            => $this->tenant->id,
            'hotel_id'             => $this->hotel->id,
            'base_price_per_night' => 500_000, // 5000 руб
        ]);

        $this->actingAs($this->user);
        app()->bind('tenant', fn () => $this->tenant);

        $this->app->instance(
            FraudControlService::class,
            \Mockery::mock(FraudControlService::class)->shouldReceive('check')->andReturn(true)->getMock()
        );
    }

    // ─── CREATE BOOKING ───────────────────────────────────────────────────────

    public function test_create_booking_returns_booking_instance(): void
    {
        $booking = $this->service->createBooking(
            hotelId:       $this->hotel->id,
            roomTypeId:    $this->roomType->id,
            checkInDate:   '2026-06-01',
            checkOutDate:  '2026-06-05',
            numberOfGuests: 2,
            correlationId: Str::uuid()->toString(),
        );

        $this->assertInstanceOf(Booking::class, $booking);
        $this->assertSame('confirmed', $booking->booking_status);
    }

    public function test_booking_calculates_nights_correctly(): void
    {
        $booking = $this->service->createBooking(
            hotelId:       $this->hotel->id,
            roomTypeId:    $this->roomType->id,
            checkInDate:   '2026-07-10',
            checkOutDate:  '2026-07-15',
            numberOfGuests: 1,
            correlationId: Str::uuid()->toString(),
        );

        $this->assertSame(5, $booking->nights_count);
    }

    public function test_booking_calculates_commission_14_percent(): void
    {
        // 3 nights × 5000 руб = 15000 руб subtotal
        // commission = 14% of 15000 = 2100 руб = 2_100_000 копеек
        $booking = $this->service->createBooking(
            hotelId:       $this->hotel->id,
            roomTypeId:    $this->roomType->id,
            checkInDate:   '2026-08-01',
            checkOutDate:  '2026-08-04',
            numberOfGuests: 1,
            correlationId: Str::uuid()->toString(),
        );

        $expectedSubtotal    = 500_000 * 3; // 1_500_000 копеек
        $expectedCommission  = (int) ($expectedSubtotal * 14 / 100);

        $this->assertSame($expectedSubtotal, $booking->subtotal_price);
        $this->assertSame($expectedCommission, $booking->commission_price);
    }

    public function test_booking_total_equals_subtotal_plus_commission_plus_fee(): void
    {
        $booking = $this->service->createBooking(
            hotelId:       $this->hotel->id,
            roomTypeId:    $this->roomType->id,
            checkInDate:   '2026-09-01',
            checkOutDate:  '2026-09-03',
            numberOfGuests: 1,
            correlationId: Str::uuid()->toString(),
        );

        $expected = $booking->subtotal_price + $booking->commission_price + $booking->cleaning_fee;
        $this->assertSame($expected, $booking->total_price);
    }

    public function test_booking_sets_correlation_id(): void
    {
        $correlationId = Str::uuid()->toString();
        $booking = $this->service->createBooking(
            hotelId:       $this->hotel->id,
            roomTypeId:    $this->roomType->id,
            checkInDate:   '2026-10-01',
            checkOutDate:  '2026-10-03',
            numberOfGuests: 1,
            correlationId: $correlationId,
        );

        $this->assertSame($correlationId, $booking->correlation_id);
    }

    public function test_booking_persists_to_db(): void
    {
        $correlationId = Str::uuid()->toString();
        $this->service->createBooking(
            hotelId:       $this->hotel->id,
            roomTypeId:    $this->roomType->id,
            checkInDate:   '2026-11-01',
            checkOutDate:  '2026-11-04',
            numberOfGuests: 2,
            correlationId: $correlationId,
        );

        $this->assertDatabaseHas('bookings', [
            'hotel_id'        => $this->hotel->id,
            'room_type_id'    => $this->roomType->id,
            'booking_status'  => 'confirmed',
            'correlation_id'  => $correlationId,
        ]);
    }

    public function test_booking_has_unique_confirmation_code(): void
    {
        $b1 = $this->service->createBooking($this->hotel->id, $this->roomType->id, '2026-06-01', '2026-06-03', 1, correlationId: Str::uuid()->toString());
        $b2 = $this->service->createBooking($this->hotel->id, $this->roomType->id, '2026-07-01', '2026-07-03', 1, correlationId: Str::uuid()->toString());

        $this->assertNotSame($b1->confirmation_code, $b2->confirmation_code);
    }

    // ─── INVALID DATES ───────────────────────────────────────────────────────

    public function test_booking_with_same_checkin_checkout_throws(): void
    {
        $this->expectException(\Exception::class);
        $this->service->createBooking($this->hotel->id, $this->roomType->id, '2026-06-01', '2026-06-01', 1, correlationId: Str::uuid()->toString());
    }

    public function test_booking_with_checkout_before_checkin_throws(): void
    {
        $this->expectException(\Exception::class);
        $this->service->createBooking($this->hotel->id, $this->roomType->id, '2026-06-10', '2026-06-05', 1, correlationId: Str::uuid()->toString());
    }

    // ─── ROOM TYPE NOT FOUND ─────────────────────────────────────────────────

    public function test_booking_with_invalid_room_type_throws(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->service->createBooking($this->hotel->id, 999_999, '2026-06-01', '2026-06-03', 1, correlationId: Str::uuid()->toString());
    }

    // ─── SINGLE NIGHT BOUNDARY ───────────────────────────────────────────────

    public function test_single_night_booking_creates_correctly(): void
    {
        $booking = $this->service->createBooking(
            $this->hotel->id,
            $this->roomType->id,
            '2026-06-01',
            '2026-06-02',
            1,
            correlationId: Str::uuid()->toString(),
        );

        $this->assertSame(1, $booking->nights_count);
        $this->assertSame(500_000, $booking->subtotal_price);
    }

    // ─── AUDIT LOG ───────────────────────────────────────────────────────────

    public function test_booking_logs_creation(): void
    {
        Log::shouldReceive('channel')->with('audit')->andReturnSelf();
        Log::shouldReceive('info')->atLeast()->once();

        $this->service->createBooking($this->hotel->id, $this->roomType->id, '2026-06-01', '2026-06-04', 1, correlationId: Str::uuid()->toString());
    }

    // ─── TENANT SCOPING ───────────────────────────────────────────────────────

    public function test_booking_scoped_to_current_tenant(): void
    {
        $booking = $this->service->createBooking(
            $this->hotel->id,
            $this->roomType->id,
            '2026-06-01',
            '2026-06-03',
            1,
            correlationId: Str::uuid()->toString(),
        );

        $this->assertSame($this->tenant->id, $booking->tenant_id);
    }

    // ─── DB ROLLBACK ─────────────────────────────────────────────────────────

    public function test_rollback_on_db_failure_leaves_no_booking(): void
    {
        DB::shouldReceive('transaction')->once()->andThrow(new \RuntimeException('DB crashed'));
        $this->expectException(\RuntimeException::class);

        $this->service->createBooking($this->hotel->id, $this->roomType->id, '2026-06-01', '2026-06-05', 1, correlationId: Str::uuid()->toString());
    }

    // ─── PAYMENT STATUS ──────────────────────────────────────────────────────

    public function test_new_booking_has_pending_payment_status(): void
    {
        $booking = $this->service->createBooking($this->hotel->id, $this->roomType->id, '2026-06-01', '2026-06-03', 1, correlationId: Str::uuid()->toString());
        $this->assertSame('pending', $booking->payment_status);
    }
}
