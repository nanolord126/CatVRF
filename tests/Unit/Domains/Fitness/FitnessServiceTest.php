<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Fitness;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for FitnessService.
 *
 * @covers \App\Domains\Fitness\Domain\Services\FitnessService
 */
final class FitnessServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Fitness\Domain\Services\FitnessService::class
        );
        $this->assertTrue($reflection->isFinal(), 'FitnessService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Fitness\Domain\Services\FitnessService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'FitnessService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Fitness\Domain\Services\FitnessService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'FitnessService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_purchaseMembership_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Fitness\Domain\Services\FitnessService::class, 'purchaseMembership'),
            'FitnessService must implement purchaseMembership()'
        );
    }

    public function test_bookSession_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Fitness\Domain\Services\FitnessService::class, 'bookSession'),
            'FitnessService must implement bookSession()'
        );
    }

    public function test_listGyms_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Fitness\Domain\Services\FitnessService::class, 'listGyms'),
            'FitnessService must implement listGyms()'
        );
    }

    public function test_listTrainers_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Fitness\Domain\Services\FitnessService::class, 'listTrainers'),
            'FitnessService must implement listTrainers()'
        );
    }

}
