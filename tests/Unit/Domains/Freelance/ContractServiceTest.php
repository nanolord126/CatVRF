<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Freelance;

use PHPUnit\Framework\TestCase;
use App\Domains\Freelance\Domain\Services\ContractService;

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
            ContractService::class
        );
        $this->assertTrue($reflection->isFinal(), 'ContractService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            ContractService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'ContractService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            ContractService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'ContractService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_create_contract_method_exists(): void
    {
        $this->assertTrue(
            method_exists(ContractService::class, 'createContract'),
            'ContractService must implement createContract()'
        );
    }

    public function test_complete_contract_method_exists(): void
    {
        $this->assertTrue(
            method_exists(ContractService::class, 'completeContract'),
            'ContractService must implement completeContract()'
        );
    }

    public function test_release_milestone_payment_method_exists(): void
    {
        $this->assertTrue(
            method_exists(ContractService::class, 'releaseMilestonePayment'),
            'ContractService must implement releaseMilestonePayment()'
        );
    }

    public function test_get_contract_method_exists(): void
    {
        $this->assertTrue(
            method_exists(ContractService::class, 'getContract'),
            'ContractService must implement getContract()'
        );
    }
}
