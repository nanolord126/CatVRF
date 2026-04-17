<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Beauty\Services;

use App\Domains\Beauty\DTOs\BeautyLoyaltyDto;
use App\Domains\Beauty\Services\BeautyLoyaltyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BeautyLoyaltyServiceTest extends TestCase
{
    use RefreshDatabase;

    private BeautyLoyaltyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BeautyLoyaltyService::class);
    }

    public function test_process_loyalty_action(): void
    {
        $dto = new BeautyLoyaltyDto(
            tenantId: 1,
            businessGroupId: null,
            userId: 1,
            action: 'appointment_completed',
            appointmentId: null,
            referralCode: null,
            correlationId: 'test-correlation',
        );

        $result = $this->service->processAction($dto);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('points_earned', $result);
        $this->assertArrayHasKey('total_points', $result);
        $this->assertTrue($result['success']);
    }

    public function test_streak_multiplier_applied(): void
    {
        $dto = new BeautyLoyaltyDto(
            tenantId: 1,
            businessGroupId: null,
            userId: 1,
            action: 'appointment_completed',
            correlationId: 'test-correlation',
        );

        $result = $this->service->processAction($dto);

        $this->assertArrayHasKey('streak_multiplier', $result);
        $this->assertGreaterThanOrEqual(1.0, $result['streak_multiplier']);
    }

    public function test_referral_bonus_applied(): void
    {
        $dto = new BeautyLoyaltyDto(
            tenantId: 1,
            businessGroupId: null,
            userId: 1,
            action: 'first_booking',
            referralCode: 'BEAUTYTEST123',
            correlationId: 'test-correlation',
        );

        $result = $this->service->processAction($dto);

        $this->assertArrayHasKey('referral_bonus', $result);
    }

    public function test_calculate_tier(): void
    {
        $status = $this->service->getLoyaltyStatus(1);

        $this->assertArrayHasKey('tier', $status);
        $this->assertContains($status['tier'], ['bronze', 'silver', 'gold', 'platinum']);
    }

    public function test_generate_referral_code(): void
    {
        $code = $this->service->generateReferralCode(1);

        $this->assertIsString($code);
        $this->assertStringStartsWith('BEAUTY', $code);
        $this->assertStringLength($code, 13);
    }
}
