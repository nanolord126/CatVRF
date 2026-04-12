<?php declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for EmployeeService.
 *
 * @covers \App\Services\HR\EmployeeService
 */
final class EmployeeServiceTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(\App\Services\HR\EmployeeService::class);
        $this->assertTrue($reflection->isFinal(), 'EmployeeService must be final');
        $this->assertTrue($reflection->isReadOnly(), 'EmployeeService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(\App\Services\HR\EmployeeService::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_hire_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Services\HR\EmployeeService::class, 'hire'),
            'EmployeeService must implement hire()'
        );
    }

    public function test_terminate_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Services\HR\EmployeeService::class, 'terminate'),
            'EmployeeService must implement terminate()'
        );
    }

    public function test_updateSalary_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Services\HR\EmployeeService::class, 'updateSalary'),
            'EmployeeService must implement updateSalary()'
        );
    }

    public function test_calculateKpiBonus_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Services\HR\EmployeeService::class, 'calculateKpiBonus'),
            'EmployeeService must implement calculateKpiBonus()'
        );
    }

}
