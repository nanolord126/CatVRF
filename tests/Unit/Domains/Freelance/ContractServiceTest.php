<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Freelance;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ContractService.
 *
 * @covers \App\Domains\Freelance\Domain\Services\ContractService
 */
final class ContractServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Freelance\Domain\Services\ContractService::class
        );
        $this->assertTrue($reflection->isFinal(), 'ContractService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Freelance\Domain\Services\ContractService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'ContractService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Freelance\Domain\Services\ContractService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'ContractService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_createContract_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Freelance\Domain\Services\ContractService::class, 'createContract'),
            'ContractService must implement createContract()'
        );
    }

    public function test_completeContract_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Freelance\Domain\Services\ContractService::class, 'completeContract'),
            'ContractService must implement completeContract()'
        );
    }

    public function test_releaseMilestonePayment_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Freelance\Domain\Services\ContractService::class, 'releaseMilestonePayment'),
            'ContractService must implement releaseMilestonePayment()'
        );
    }

    public function test_getContract_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Freelance\Domain\Services\ContractService::class, 'getContract'),
            'ContractService must implement getContract()'
        );
    }

}
