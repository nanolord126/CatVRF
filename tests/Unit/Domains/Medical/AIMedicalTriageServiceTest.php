<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Medical;

use PHPUnit\Framework\TestCase;
use App\Domains\Medical\Domain\Services\AIMedicalTriageService;

/**
 * Unit tests for AIMedicalTriageService.
 *
 * @covers \App\Domains\Medical\Domain\Services\AIMedicalTriageService
 */
final class AIMedicalTriageServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            AIMedicalTriageService::class
        );
        $this->assertTrue($reflection->isFinal(), 'AIMedicalTriageService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            AIMedicalTriageService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'AIMedicalTriageService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            AIMedicalTriageService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'AIMedicalTriageService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_analyze_symptoms_method_exists(): void
    {
        $this->assertTrue(
            method_exists(AIMedicalTriageService::class, 'analyzeSymptoms'),
            'AIMedicalTriageService must implement analyzeSymptoms()'
        );
    }
}
