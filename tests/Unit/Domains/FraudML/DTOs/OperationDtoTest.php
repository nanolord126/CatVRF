<?php declare(strict_types=1);

namespace Tests\Unit\Domains\FraudML\DTOs;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for OperationDto.
 *
 * @covers \App\Domains\FraudML\DTOs\OperationDto
 */
final class OperationDtoTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\FraudML\DTOs\OperationDto::class
        );
        $this->assertTrue($reflection->isFinal(), 'OperationDto must be final');
        $this->assertTrue($reflection->isReadOnly(), 'OperationDto must be readonly');
    }

    public function test_constructor_properties(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\FraudML\DTOs\OperationDto::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $params = array_map(fn($p) => $p->getName(), $constructor->getParameters());
        $this->assertContains('tenant_id', $params, 'Constructor must have tenant_id');
        $this->assertContains('user_id', $params, 'Constructor must have user_id');
        $this->assertContains('operation_type', $params, 'Constructor must have operation_type');
        $this->assertContains('amount', $params, 'Constructor must have amount');
        $this->assertContains('ip_address', $params, 'Constructor must have ip_address');
        $this->assertContains('device_fingerprint', $params, 'Constructor must have device_fingerprint');
        $this->assertContains('correlation_id', $params, 'Constructor must have correlation_id');
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
        return \App\Domains\FraudML\DTOs\OperationDto::class;
    }
}
