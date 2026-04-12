<?php declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AnonymizationService.
 *
 * @covers \App\Services\ML\AnonymizationService
 */
final class AnonymizationServiceTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(\App\Services\ML\AnonymizationService::class);
        $this->assertTrue($reflection->isFinal(), 'AnonymizationService must be final');
        $this->assertTrue($reflection->isReadOnly(), 'AnonymizationService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(\App\Services\ML\AnonymizationService::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_anonymizeUserId_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Services\ML\AnonymizationService::class, 'anonymizeUserId'),
            'AnonymizationService must implement anonymizeUserId()'
        );
    }

    public function test_generalizeGeo_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Services\ML\AnonymizationService::class, 'generalizeGeo'),
            'AnonymizationService must implement generalizeGeo()'
        );
    }

    public function test_hashCity_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Services\ML\AnonymizationService::class, 'hashCity'),
            'AnonymizationService must implement hashCity()'
        );
    }

    public function test_anonymizeEvent_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Services\ML\AnonymizationService::class, 'anonymizeEvent'),
            'AnonymizationService must implement anonymizeEvent()'
        );
    }

    public function test_anonymizeBehaviorBatch_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Services\ML\AnonymizationService::class, 'anonymizeBehaviorBatch'),
            'AnonymizationService must implement anonymizeBehaviorBatch()'
        );
    }

    public function test_anonymizeMarketingEvent_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Services\ML\AnonymizationService::class, 'anonymizeMarketingEvent'),
            'AnonymizationService must implement anonymizeMarketingEvent()'
        );
    }

}
