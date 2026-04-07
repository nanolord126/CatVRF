<?php declare(strict_types=1);

namespace App\Domains\Luxury\Tests;

use Tests\TestCase;

final class ConciergeVipBookingTest extends TestCase
{

    use RefreshDatabase;

        private string $correlationId;

        protected function setUp(): void
        {
            parent::setUp();
            $this->correlationId = (string) Str::uuid();
        }

        /**
         * Тест создания VIP-бронирования
         */
        public function test_it_creates_vip_booking_successfully(): void
        {
            // 1. Arrange (настройка данных)
            $client = LuxuryClient::factory()->create([
                'vip_level' => 'platinum',
                'user_id' => 1
            ]);

            $product = LuxuryProduct::factory()->create([
                'name' => 'Exclusive Watch VMF 2026',
                'current_stock' => 1,
                'price_kopecks' => 100000000 // 1 000 000 руб
            ]);

            // 2. Mock Fraud Check
            $this->mock(FraudControlService::class, function (MockInterface $mock) {
                $mock->shouldReceive('check')->once()->andReturn(true);
            });

            // 3. Act (выполнение действия)
            $service = new ConciergeService(app(FraudControlService::class), $this->correlationId);

            $booking = $service->createBooking($client, $product, [
                'booking_at' => now()->addDays(2),
                'notes' => 'Test VIP notes',
                'total_price_kopecks' => 100000000,
                'deposit_kopecks' => 10000000,
            ]);

            // 4. Assert (проверка результатов)
            $this->assertInstanceOf(VIPBooking::class, $booking);
            $this->assertEquals('pending', $booking->status);
            $this->assertEquals($client->id, $booking->client_id);
            $this->assertEquals($this->correlationId, $booking->correlation_id);

            // Проверка холда стока
            $this->assertEquals(1, $product->fresh()->hold_stock);

            // Проверка в базе
            $this->assertDatabaseHas('luxury_vip_bookings', [
                'uuid' => $booking->uuid,
                'client_id' => $client->id,
                'bookable_type' => LuxuryProduct::class,
                'bookable_id' => $product->id,
                'correlation_id' => $this->correlationId,
            ]);

            // Проверка Audit Log (симуляция)
            // $this->assertLogged('VIP Booking Created');
        }

        /**
         * Тест отмены из-за отсутствия стока
         */
        public function test_it_fails_booking_when_out_of_stock(): void
        {
            $client = LuxuryClient::factory()->create();
            $product = LuxuryProduct::factory()->create(['current_stock' => 0]);

            $this->mock(FraudControlService::class, function (MockInterface $mock) {
                $mock->shouldReceive('check')->andReturn(true);
            });

            $service = new ConciergeService(app(FraudControlService::class), $this->correlationId);

            $this->expectException(\App\Domains\Luxury\Exceptions\LuxuryServiceException::class);

            $service->createBooking($client, $product, [
                'booking_at' => now()->addDays(2),
            ]);
        }
}
