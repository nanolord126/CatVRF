<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Payment\DTOs;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for UpdatePaymentRecordDto.
 *
 * @covers \App\Domains\Payment\DTOs\UpdatePaymentRecordDto
 */
final class UpdatePaymentRecordDtoTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Payment\DTOs\UpdatePaymentRecordDto::class
        );
        $this->assertTrue($reflection->isFinal(), 'UpdatePaymentRecordDto must be final');
        $this->assertTrue($reflection->isReadOnly(), 'UpdatePaymentRecordDto must be readonly');
    }

    public function test_constructor_properties(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Payment\DTOs\UpdatePaymentRecordDto::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $params = array_map(fn($p) => $p->getName(), $constructor->getParameters());
        $this->assertContains('paymentRecordId', $params, 'Constructor must have paymentRecordId');
        $this->assertContains('status', $params, 'Constructor must have status');
        $this->assertContains('correlationId', $params, 'Constructor must have correlationId');
        $this->assertContains('providerPaymentId', $params, 'Constructor must have providerPaymentId');
        $this->assertContains('providerResponse', $params, 'Constructor must have providerResponse');
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
        return \App\Domains\Payment\DTOs\UpdatePaymentRecordDto::class;
    }
}
