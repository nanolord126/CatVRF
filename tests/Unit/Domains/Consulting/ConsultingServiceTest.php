<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Consulting;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ConsultingService.
 *
 * @covers \App\Domains\Consulting\Domain\Services\ConsultingService
 */
final class ConsultingServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Consulting\Domain\Services\ConsultingService::class
        );
        $this->assertTrue($reflection->isFinal(), 'ConsultingService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Consulting\Domain\Services\ConsultingService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'ConsultingService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Consulting\Domain\Services\ConsultingService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'ConsultingService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_createProject_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Consulting\Domain\Services\ConsultingService::class, 'createProject'),
            'ConsultingService must implement createProject()'
        );
    }

    public function test_completeProject_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Consulting\Domain\Services\ConsultingService::class, 'completeProject'),
            'ConsultingService must implement completeProject()'
        );
    }

    public function test_cancelProject_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Consulting\Domain\Services\ConsultingService::class, 'cancelProject'),
            'ConsultingService must implement cancelProject()'
        );
    }

    public function test_getProject_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Consulting\Domain\Services\ConsultingService::class, 'getProject'),
            'ConsultingService must implement getProject()'
        );
    }

    public function test_getUserProjects_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Consulting\Domain\Services\ConsultingService::class, 'getUserProjects'),
            'ConsultingService must implement getUserProjects()'
        );
    }

}
