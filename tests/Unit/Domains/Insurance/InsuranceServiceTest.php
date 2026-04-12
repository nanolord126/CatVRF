<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Insurance;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for InsuranceService.
 *
 * @covers \App\Domains\Insurance\Domain\Services\InsuranceService
 */
final class InsuranceServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Insurance\Domain\Services\InsuranceService::class
        );
        $this->assertTrue($reflection->isFinal(), 'InsuranceService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Insurance\Domain\Services\InsuranceService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'InsuranceService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Insurance\Domain\Services\InsuranceService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'InsuranceService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_createPolicy_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Insurance\Domain\Services\InsuranceService::class, 'createPolicy'),
            'InsuranceService must implement createPolicy()'
        );
    }

    public function test_activatePolicy_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Insurance\Domain\Services\InsuranceService::class, 'activatePolicy'),
            'InsuranceService must implement activatePolicy()'
        );
    }

    public function test_cancelPolicy_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Insurance\Domain\Services\InsuranceService::class, 'cancelPolicy'),
            'InsuranceService must implement cancelPolicy()'
        );
    }

    public function test_getPolicy_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Insurance\Domain\Services\InsuranceService::class, 'getPolicy'),
            'InsuranceService must implement getPolicy()'
        );
    }

    public function test_getUserPolicies_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Insurance\Domain\Services\InsuranceService::class, 'getUserPolicies'),
            'InsuranceService must implement getUserPolicies()'
        );
    }

}
