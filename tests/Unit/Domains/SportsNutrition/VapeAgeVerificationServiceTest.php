<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\SportsNutrition;

use PHPUnit\Framework\TestCase;
use App\Domains\SportsNutrition\Domain\Services\VapeAgeVerificationService;

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
            VapeAgeVerificationService::class
        );
        $this->assertTrue($reflection->isFinal(), 'VapeAgeVerificationService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            VapeAgeVerificationService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'VapeAgeVerificationService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            VapeAgeVerificationService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'VapeAgeVerificationService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_initiate_verification_method_exists(): void
    {
        $this->assertTrue(
            method_exists(VapeAgeVerificationService::class, 'initiateVerification'),
            'VapeAgeVerificationService must implement initiateVerification()'
        );
    }

    public function test_complete_verification_method_exists(): void
    {
        $this->assertTrue(
            method_exists(VapeAgeVerificationService::class, 'completeVerification'),
            'VapeAgeVerificationService must implement completeVerification()'
        );
    }

    public function test_has_a_valid_verification_method_exists(): void
    {
        $this->assertTrue(
            method_exists(VapeAgeVerificationService::class, 'hasAValidVerification'),
            'VapeAgeVerificationService must implement hasAValidVerification()'
        );
    }
}
