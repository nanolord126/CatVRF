<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Freelance;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for FreelanceService.
 *
 * @covers \App\Domains\Freelance\Domain\Services\FreelanceService
 */
final class FreelanceServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Freelance\Domain\Services\FreelanceService::class
        );
        $this->assertTrue($reflection->isFinal(), 'FreelanceService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Freelance\Domain\Services\FreelanceService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'FreelanceService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Freelance\Domain\Services\FreelanceService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'FreelanceService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_createProject_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Freelance\Domain\Services\FreelanceService::class, 'createProject'),
            'FreelanceService must implement createProject()'
        );
    }

    public function test_submitProposal_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Freelance\Domain\Services\FreelanceService::class, 'submitProposal'),
            'FreelanceService must implement submitProposal()'
        );
    }

    public function test_acceptProposal_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Freelance\Domain\Services\FreelanceService::class, 'acceptProposal'),
            'FreelanceService must implement acceptProposal()'
        );
    }

    public function test_getOpenProjects_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Freelance\Domain\Services\FreelanceService::class, 'getOpenProjects'),
            'FreelanceService must implement getOpenProjects()'
        );
    }

    public function test_getProject_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Freelance\Domain\Services\FreelanceService::class, 'getProject'),
            'FreelanceService must implement getProject()'
        );
    }

}
