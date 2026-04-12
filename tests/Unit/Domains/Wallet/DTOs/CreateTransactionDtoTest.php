<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Wallet\DTOs;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CreateTransactionDto.
 *
 * @covers \App\Domains\Wallet\DTOs\CreateTransactionDto
 */
final class CreateTransactionDtoTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Wallet\DTOs\CreateTransactionDto::class
        );
        $this->assertTrue($reflection->isFinal(), 'CreateTransactionDto must be final');
        $this->assertTrue($reflection->isReadOnly(), 'CreateTransactionDto must be readonly');
    }

    public function test_constructor_properties(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Wallet\DTOs\CreateTransactionDto::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $params = array_map(fn($p) => $p->getName(), $constructor->getParameters());
        $this->assertContains('walletId', $params, 'Constructor must have walletId');
        $this->assertContains('tenantId', $params, 'Constructor must have tenantId');
        $this->assertContains('businessGroupId', $params, 'Constructor must have businessGroupId');
        $this->assertContains('amount', $params, 'Constructor must have amount');
        $this->assertContains('type', $params, 'Constructor must have type');
        $this->assertContains('correlationId', $params, 'Constructor must have correlationId');
        $this->assertContains('idempotencyKey', $params, 'Constructor must have idempotencyKey');
        $this->assertContains('description', $params, 'Constructor must have description');
    }

    public function test_has_toArray_method(): void
    {
        $this->assertTrue(
            method_exists($this->getDtoClass(), 'toArray'),
            'DTO must implement toArray()'
        );
    }

    private function getDtoClass(): string
    {
        return \App\Domains\Wallet\DTOs\CreateTransactionDto::class;
    }
}
