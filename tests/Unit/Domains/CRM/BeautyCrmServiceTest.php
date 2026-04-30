<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\CRM;

use PHPUnit\Framework\TestCase;
use App\Domains\CRM\Services\BeautyCrmService;

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
            BeautyCrmService::class
        );
        $this->assertTrue($reflection->isFinal(), 'BeautyCrmService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            BeautyCrmService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'BeautyCrmService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            BeautyCrmService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'BeautyCrmService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_create_beauty_profile_method_exists(): void
    {
        $this->assertTrue(
            method_exists(BeautyCrmService::class, 'createBeautyProfile'),
            'BeautyCrmService must implement createBeautyProfile()'
        );
    }

    public function test_update_medical_card_method_exists(): void
    {
        $this->assertTrue(
            method_exists(BeautyCrmService::class, 'updateMedicalCard'),
            'BeautyCrmService must implement updateMedicalCard()'
        );
    }

    public function test_add_before_after_photo_method_exists(): void
    {
        $this->assertTrue(
            method_exists(BeautyCrmService::class, 'addBeforeAfterPhoto'),
            'BeautyCrmService must implement addBeforeAfterPhoto()'
        );
    }

    public function test_check_allergies_method_exists(): void
    {
        $this->assertTrue(
            method_exists(BeautyCrmService::class, 'checkAllergies'),
            'BeautyCrmService must implement checkAllergies()'
        );
    }

    public function test_record_visit_method_exists(): void
    {
        $this->assertTrue(
            method_exists(BeautyCrmService::class, 'recordVisit'),
            'BeautyCrmService must implement recordVisit()'
        );
    }
}
