<?php declare(strict_types=1);

namespace Tests\Feature\Hotels;

use App\Domains\Hotels\Models\Hotel;
use App\Domains\Hotels\Models\Room;
use App\Domains\Hotels\Services\HotelBookingService;
use App\Services\FraudControlService;
use App\Services\WalletService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\TestCase;
use Mockery;

/**
 * КАНОН 2026: Hotel Booking Flow Test (Layer 9)
 * 
 * Тестирование основных сценариев бронирования.
 * Прoвeрка: tenant scoping, correlation_id, audit logs.
 */
final class HotelBookingFlowTest extends TestCase
{
    // use RefreshDatabase; // Применяем только для локальных БД

    private HotelBookingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Мокаем зависимости сервиса
        $fraud = Mockery::mock(FraudControlService::class);
        $fraud->shouldReceive('checkOperation')->andReturn(true);

        $wallet = Mockery::mock(WalletService::class);
        $payment = Mockery::mock(PaymentService::class);

        $this->service = new HotelBookingService($fraud, $wallet, $payment, (string) Str::uuid());
    }

    /**
     * @test
     * @testdox Прoвeрка инициaции бронирования с correlation_id
     */
    public function it_initiates_booking_successfully(): void
    {
        Log::shouldReceive('channel')->with('audit')->andReturnSelf();
        Log::shouldReceive('info')->atLeast()->times(1);

        // Создаем отель и номер (фабрики или моки)
        // Для теста используем реальные модели с tenant() контекстом
        
        $hotel = Hotel::factory()->create([
            'tenant_id' => 1,
            'is_active' => true,
        ]);

        $room = Room::factory()->create([
            'hotel_id' => $hotel->id,
            'tenant_id' => 1,
            'is_available' => true,
            'total_stock' => 5,
            'min_stay_days' => 1,
            'base_price_b2c' => 500000, // 5000 Руб
        ]);

        $bookingData = [
            'room_id' => $room->id,
            'check_in' => now()->addDay()->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
            'is_b2b' => false,
        ];

        // Запуск
        $booking = $this->service->initiateBooking($bookingData);

        // Ассерты
        $this->assertNotNull($booking);
        $this->assertEquals('pending', $booking->status);
        $this->assertEquals(1, $booking->tenant_id);
        $this->assertNotNull($booking->uuid);
        $this->assertNotNull($booking->correlation_id);
        
        // Прoвeрка инвентаря
        $this->assertEquals(4, $room->fresh()->total_stock);
    }
}
