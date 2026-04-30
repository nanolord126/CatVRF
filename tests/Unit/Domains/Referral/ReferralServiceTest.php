<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Referral;

use PHPUnit\Framework\TestCase;
use App\Domains\Referral\Domain\Services\ReferralService;

/**
 * Unit tests for ReferralService.
 *
 * @covers \App\Domains\Referral\Domain\Services\ReferralService
 */
final class ReferralServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            ReferralService::class
        );
        $this->assertTrue($reflection->isFinal(), 'ReferralService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            ReferralService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'ReferralService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            ReferralService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'ReferralService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_generate_referral_link_method_exists(): void
    {
        $this->assertTrue(
            method_exists(ReferralService::class, 'generateReferralLink'),
            'ReferralService must implement generateReferralLink()'
        );
    }

    public function test_register_referral_method_exists(): void
    {
        $this->assertTrue(
            method_exists(ReferralService::class, 'registerReferral'),
            'ReferralService must implement registerReferral()'
        );
    }

    public function test_check_qualification_method_exists(): void
    {
        $this->assertTrue(
            method_exists(ReferralService::class, 'checkQualification'),
            'ReferralService must implement checkQualification()'
        );
    }

    public function test_award_bonus_method_exists(): void
    {
        $this->assertTrue(
            method_exists(ReferralService::class, 'awardBonus'),
            'ReferralService must implement awardBonus()'
        );
    }

    public function test_validate_migration_method_exists(): void
    {
        $this->assertTrue(
            method_exists(ReferralService::class, 'validateMigration'),
            'ReferralService must implement validateMigration()'
        );
    }
}
