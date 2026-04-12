<?php declare(strict_types=1);

namespace Tests\Unit\Domains\SportsNutrition;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for VapeAgeVerificationService.
 *
 * @covers \App\Domains\SportsNutrition\Domain\Services\VapeAgeVerificationService
 */
final class VapeAgeVerificationServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\SportsNutrition\Domain\Services\VapeAgeVerificationService::class
        );
        $this->assertTrue($reflection->isFinal(), 'VapeAgeVerificationService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\SportsNutrition\Domain\Services\VapeAgeVerificationService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'VapeAgeVerificationService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\SportsNutrition\Domain\Services\VapeAgeVerificationService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'VapeAgeVerificationService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_initiateVerification_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\SportsNutrition\Domain\Services\VapeAgeVerificationService::class, 'initiateVerification'),
            'VapeAgeVerificationService must implement initiateVerification()'
        );
    }

    public function test_completeVerification_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\SportsNutrition\Domain\Services\VapeAgeVerificationService::class, 'completeVerification'),
            'VapeAgeVerificationService must implement completeVerification()'
        );
    }

    public function test_hasAValidVerification_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\SportsNutrition\Domain\Services\VapeAgeVerificationService::class, 'hasAValidVerification'),
            'VapeAgeVerificationService must implement hasAValidVerification()'
        );
    }

}
