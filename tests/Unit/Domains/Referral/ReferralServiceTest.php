<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Referral;

use PHPUnit\Framework\TestCase;

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
            \App\Domains\Referral\Domain\Services\ReferralService::class
        );
        $this->assertTrue($reflection->isFinal(), 'ReferralService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Referral\Domain\Services\ReferralService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'ReferralService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Referral\Domain\Services\ReferralService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'ReferralService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_generateReferralLink_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Referral\Domain\Services\ReferralService::class, 'generateReferralLink'),
            'ReferralService must implement generateReferralLink()'
        );
    }

    public function test_registerReferral_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Referral\Domain\Services\ReferralService::class, 'registerReferral'),
            'ReferralService must implement registerReferral()'
        );
    }

    public function test_checkQualification_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Referral\Domain\Services\ReferralService::class, 'checkQualification'),
            'ReferralService must implement checkQualification()'
        );
    }

    public function test_awardBonus_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Referral\Domain\Services\ReferralService::class, 'awardBonus'),
            'ReferralService must implement awardBonus()'
        );
    }

    public function test_validateMigration_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Referral\Domain\Services\ReferralService::class, 'validateMigration'),
            'ReferralService must implement validateMigration()'
        );
    }

}
