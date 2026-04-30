<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\ML\AnonymizationService;

/**
 * Unit tests for AnonymizationService.
 *
 * @covers \App\Services\ML\AnonymizationService
 */
final class AnonymizationServiceTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(AnonymizationService::class);
        $this->assertTrue($reflection->isFinal(), 'AnonymizationService must be final');
        $this->assertTrue($reflection->isReadOnly(), 'AnonymizationService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(AnonymizationService::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_anonymize_user_id_method_exists(): void
    {
        $this->assertTrue(
            method_exists(AnonymizationService::class, 'anonymizeUserId'),
            'AnonymizationService must implement anonymizeUserId()'
        );
    }

    public function test_generalize_geo_method_exists(): void
    {
        $this->assertTrue(
            method_exists(AnonymizationService::class, 'generalizeGeo'),
            'AnonymizationService must implement generalizeGeo()'
        );
    }

    public function test_hash_city_method_exists(): void
    {
        $this->assertTrue(
            method_exists(AnonymizationService::class, 'hashCity'),
            'AnonymizationService must implement hashCity()'
        );
    }

    public function test_anonymize_event_method_exists(): void
    {
        $this->assertTrue(
            method_exists(AnonymizationService::class, 'anonymizeEvent'),
            'AnonymizationService must implement anonymizeEvent()'
        );
    }

    public function test_anonymize_behavior_batch_method_exists(): void
    {
        $this->assertTrue(
            method_exists(AnonymizationService::class, 'anonymizeBehaviorBatch'),
            'AnonymizationService must implement anonymizeBehaviorBatch()'
        );
    }

    public function test_anonymize_marketing_event_method_exists(): void
    {
        $this->assertTrue(
            method_exists(AnonymizationService::class, 'anonymizeMarketingEvent'),
            'AnonymizationService must implement anonymizeMarketingEvent()'
        );
    }
}
