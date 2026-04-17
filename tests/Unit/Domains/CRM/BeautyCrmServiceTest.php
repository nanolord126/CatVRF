<?php declare(strict_types=1);

namespace Tests\Unit\Domains\CRM;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for BeautyCrmService.
 *
 * @covers \App\Domains\CRM\Services\BeautyCrmService
 */
final class BeautyCrmServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\CRM\Services\BeautyCrmService::class
        );
        $this->assertTrue($reflection->isFinal(), 'BeautyCrmService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\CRM\Services\BeautyCrmService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'BeautyCrmService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\CRM\Services\BeautyCrmService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'BeautyCrmService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_createBeautyProfile_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\CRM\Services\BeautyCrmService::class, 'createBeautyProfile'),
            'BeautyCrmService must implement createBeautyProfile()'
        );
    }

    public function test_updateMedicalCard_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\CRM\Services\BeautyCrmService::class, 'updateMedicalCard'),
            'BeautyCrmService must implement updateMedicalCard()'
        );
    }

    public function test_addBeforeAfterPhoto_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\CRM\Services\BeautyCrmService::class, 'addBeforeAfterPhoto'),
            'BeautyCrmService must implement addBeforeAfterPhoto()'
        );
    }

    public function test_checkAllergies_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\CRM\Services\BeautyCrmService::class, 'checkAllergies'),
            'BeautyCrmService must implement checkAllergies()'
        );
    }

    public function test_recordVisit_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\CRM\Services\BeautyCrmService::class, 'recordVisit'),
            'BeautyCrmService must implement recordVisit()'
        );
    }

}
