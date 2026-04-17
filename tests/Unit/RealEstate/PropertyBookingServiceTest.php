<?php declare(strict_types=1);

namespace Tests\Unit\RealEstate;

use Tests\TestCase;
use Modules\RealEstate\Services\PropertyBookingService;
use Illuminate\Support\Str;

final class PropertyBookingServiceTest extends TestCase
{
    private PropertyBookingService $bookingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bookingService = app(PropertyBookingService::class);
    }

    public function test_calculate_fraud_score(): void
    {
        $reflection = new \ReflectionClass($this->bookingService);
        $method = $reflection->getMethod('calculateFraudScore');
        $method->setAccessible(true);

        $data = [
            'user_id' => 1,
        ];
        $property = new \Modules\RealEstate\Models\Property([
            'price' => 10000000,
            'area' => 100,
            'city' => 'Москва',
        ]);

        $score = $method->invoke($this->bookingService, $data, $property, false);

        $this->assertIsFloat($score);
        $this->assertGreaterThanOrEqual(0.0, $score);
        $this->assertLessThanOrEqual(1.0, $score);
    }

    public function test_calculate_deal_score(): void
    {
        $reflection = new \ReflectionClass($this->bookingService);
        $method = $reflection->getMethod('calculateDealScore');
        $method->setAccessible(true);

        $data = ['user_id' => 1];
        $property = new \Modules\RealEstate\Models\Property([
            'id' => 1,
            'city' => 'Москва',
            'price' => 10000000,
        ]);

        $score = $method->invoke($this->bookingService, $data, $property, false);

        $this->assertIsArray($score);
        $this->assertArrayHasKey('overall', $score);
        $this->assertArrayHasKey('credit', $score);
        $this->assertArrayHasKey('legal', $score);
        $this->assertArrayHasKey('liquidity', $score);
        $this->assertArrayHasKey('recommended', $score);
        $this->assertGreaterThanOrEqual(0.0, $score['overall']);
        $this->assertLessThanOrEqual(1.0, $score['overall']);
    }

    public function test_calculate_dynamic_price(): void
    {
        $reflection = new \ReflectionClass($this->bookingService);
        $method = $reflection->getMethod('calculateDynamicPrice');
        $method->setAccessible(true);

        $property = new \Modules\RealEstate\Models\Property([
            'price' => 10000000,
        ]);

        $price = $method->invoke($this->bookingService, $property, now()->addDays(2), false);

        $this->assertIsFloat($price);
        $this->assertGreaterThan(0, $price);
    }

    public function test_predict_demand(): void
    {
        $reflection = new \ReflectionClass($this->bookingService);
        $method = $reflection->getMethod('predictDemand');
        $method->setAccessible(true);

        $multiplier = $method->invoke($this->bookingService, 1, now()->addDay()->setHour(18));

        $this->assertIsFloat($multiplier);
        $this->assertGreaterThanOrEqual(1.0, $multiplier);
    }

    public function test_b2b_commission_split(): void
    {
        $reflection = new \ReflectionClass($this->bookingService);
        $method = $reflection->getMethod('calculateB2BCommissionSplit');
        $method->setAccessible(true);

        $split = $method->invoke($this->bookingService, 10000000);

        $this->assertIsArray($split);
        $this->assertArrayHasKey('platform', $split);
        $this->assertArrayHasKey('agent', $split);
        $this->assertArrayHasKey('referral', $split);
        $this->assertArrayHasKey('total', $split);
        $this->assertEquals(1300000, $split['total']);
    }

    public function test_hold_slot_b2c_vs_b2b(): void
    {
        $reflection = new \ReflectionClass($this->bookingService);
        $constant = $reflection->getConstant('HOLD_SLOT_B2C_MINUTES');
        $constantB2B = $reflection->getConstant('HOLD_SLOT_B2B_MINUTES');

        $this->assertEquals(15, $constant);
        $this->assertEquals(60, $constantB2B);
    }
}
