<?php declare(strict_types=1);

namespace Tests\Unit\Domains\PersonalDevelopment;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PersonalDevelopmentService.
 *
 * @covers \App\Domains\PersonalDevelopment\Domain\Services\PersonalDevelopmentService
 */
final class PersonalDevelopmentServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\PersonalDevelopment\Domain\Services\PersonalDevelopmentService::class
        );
        $this->assertTrue($reflection->isFinal(), 'PersonalDevelopmentService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\PersonalDevelopment\Domain\Services\PersonalDevelopmentService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'PersonalDevelopmentService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\PersonalDevelopment\Domain\Services\PersonalDevelopmentService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'PersonalDevelopmentService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_createProgram_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\PersonalDevelopment\Domain\Services\PersonalDevelopmentService::class, 'createProgram'),
            'PersonalDevelopmentService must implement createProgram()'
        );
    }

    public function test_publishProgram_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\PersonalDevelopment\Domain\Services\PersonalDevelopmentService::class, 'publishProgram'),
            'PersonalDevelopmentService must implement publishProgram()'
        );
    }

    public function test_updateProgram_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\PersonalDevelopment\Domain\Services\PersonalDevelopmentService::class, 'updateProgram'),
            'PersonalDevelopmentService must implement updateProgram()'
        );
    }

    public function test_archiveProgram_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\PersonalDevelopment\Domain\Services\PersonalDevelopmentService::class, 'archiveProgram'),
            'PersonalDevelopmentService must implement archiveProgram()'
        );
    }

    public function test_getActivePrograms_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\PersonalDevelopment\Domain\Services\PersonalDevelopmentService::class, 'getActivePrograms'),
            'PersonalDevelopmentService must implement getActivePrograms()'
        );
    }

}
