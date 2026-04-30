<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Electronics;

use PHPUnit\Framework\TestCase;
use App\Domains\Electronics\Domain\Services\ElectronicsAIConstructorService;

/**
 * Unit tests for ElectronicsAIConstructorService.
 *
 * @covers \App\Domains\Electronics\Domain\Services\ElectronicsAIConstructorService
 */
final class ElectronicsAIConstructorServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            ElectronicsAIConstructorService::class
        );
        $this->assertTrue($reflection->isFinal(), 'ElectronicsAIConstructorService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            ElectronicsAIConstructorService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'ElectronicsAIConstructorService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            ElectronicsAIConstructorService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'ElectronicsAIConstructorService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_suggest_compatibility_method_exists(): void
    {
        $this->assertTrue(
            method_exists(ElectronicsAIConstructorService::class, 'suggestCompatibility'),
            'ElectronicsAIConstructorService must implement suggestCompatibility()'
        );
    }

    public function test_save_design_draft_method_exists(): void
    {
        $this->assertTrue(
            method_exists(ElectronicsAIConstructorService::class, 'saveDesignDraft'),
            'ElectronicsAIConstructorService must implement saveDesignDraft()'
        );
    }
}
