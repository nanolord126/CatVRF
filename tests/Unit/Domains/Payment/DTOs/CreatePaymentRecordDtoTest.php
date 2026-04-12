<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Payment\DTOs;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CreatePaymentRecordDto.
 *
 * @covers \App\Domains\Payment\DTOs\CreatePaymentRecordDto
 */
final class CreatePaymentRecordDtoTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Payment\DTOs\CreatePaymentRecordDto::class
        );
        $this->assertTrue($reflection->isFinal(), 'CreatePaymentRecordDto must be final');
        $this->assertTrue($reflection->isReadOnly(), 'CreatePaymentRecordDto must be readonly');
    }

    public function test_constructor_properties(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Payment\DTOs\CreatePaymentRecordDto::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $params = array_map(fn($p) => $p->getName(), $constructor->getParameters());
        $this->assertContains('tenantId', $params, 'Constructor must have tenantId');
        $this->assertContains('businessGroupId', $params, 'Constructor must have businessGroupId');
        $this->assertContains('providerCode', $params, 'Constructor must have providerCode');
        $this->assertContains('amountKopecks', $params, 'Constructor must have amountKopecks');
        $this->assertContains('idempotencyKey', $params, 'Constructor must have idempotencyKey');
        $this->assertContains('correlationId', $params, 'Constructor must have correlationId');
        $this->assertContains('isHold', $params, 'Constructor must have isHold');
        $this->assertContains('description', $params, 'Constructor must have description');
        $this->assertContains('metadata', $params, 'Constructor must have metadata');
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
        return \App\Domains\Payment\DTOs\CreatePaymentRecordDto::class;
    }
}
