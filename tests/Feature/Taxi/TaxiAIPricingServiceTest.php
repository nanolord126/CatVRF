<?php

namespace Tests\Feature\Taxi;

use Tests\TestCase;
use App\Models\Tenant;
use App\Services\Taxi\TaxiAIPricingService;
use Carbon\Carbon;

class TaxiAIPricingServiceTest extends TestCase
{
    protected TaxiAIPricingService $pricingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pricingService = new TaxiAIPricingService();
    }

    /** @test */
    public function it_calculates_base_economy_price_correctly()
    {
        // 1. Фиксируем время на будний день (не ночь, не час пик)
        Carbon::setTestNow(Carbon::create(2026, 3, 6, 14, 0, 0)); // Пятница 14:00

        // 2. Рассчитываем 10 км на Экономе
        $result = $this->pricingService->calculate(10, 'economy');

        // 3. Формула: 100 (посадка) + 10 * 20 (дистанция) = 300
        $this->assertEquals(300.0, $result['amount']);
        $this->assertEquals(1.0, $result['time_factor']);
    }

    /** @test */
    public function it_applies_night_multiplier_correctly()
    {
        // 1. Фиксируем ночное время
        Carbon::setTestNow(Carbon::create(2026, 3, 6, 2, 0, 0)); // 2:00 ночи

        // 2. Рассчитываем 10 км на Комфорте
        $result = $this->pricingService->calculate(10, 'comfort');

        // 3. Формула: (250 + 10 * 45) * 1.25 = 700 * 1.25 = 875
        $this->assertEquals(875.0, $result['amount']);
        $this->assertEquals(1.25, $result['time_factor']);
    }

    /** @test */
    public function it_applies_peak_hour_multiplier_correctly()
    {
        // 1. Фиксируем час пик (утро 9:00)
        Carbon::setTestNow(Carbon::create(2026, 3, 6, 9, 0, 0));

        // 2. Рассчитываем 10 км на Бизнесе
        $result = $this->pricingService->calculate(10, 'business');

        // 3. Формула: (500 + 10 * 85) * 1.35 = 1350 * 1.35 = 1822.5
        $this->assertEquals(1822.5, $result['amount']);
        $this->assertEquals(1.35, $result['time_factor']);
    }
}
