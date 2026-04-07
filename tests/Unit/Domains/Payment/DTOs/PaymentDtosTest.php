<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Payment\DTOs;

use App\Domains\Payment\DTOs\CreatePaymentRecordDto;
use App\Domains\Payment\DTOs\UpdatePaymentRecordDto;
use PHPUnit\Framework\TestCase;

/**
 * Unit-тесты для Payment DTOs.
 */
final class PaymentDtosTest extends TestCase
{
    // ─── CreatePaymentRecordDto ──────────────────────────────────

    public function test_create_dto_constructor(): void
    {
        $dto = new CreatePaymentRecordDto(
            tenantId: 1,
            businessGroupId: 2,
            providerCode: 'tinkoff',
            amountKopecks: 100000,
            idempotencyKey: 'idem-123',
            correlationId: 'corr-456',
            isHold: true,
            description: 'Test payment',
            metadata: ['key' => 'value'],
        );

        $this->assertSame(1, $dto->tenantId);
        $this->assertSame(2, $dto->businessGroupId);
        $this->assertSame('tinkoff', $dto->providerCode);
        $this->assertSame(100000, $dto->amountKopecks);
        $this->assertSame('idem-123', $dto->idempotencyKey);
        $this->assertSame('corr-456', $dto->correlationId);
        $this->assertTrue($dto->isHold);
        $this->assertSame('Test payment', $dto->description);
        $this->assertSame(['key' => 'value'], $dto->metadata);
    }

    public function test_create_dto_to_array(): void
    {
        $dto = new CreatePaymentRecordDto(
            tenantId: 1,
            businessGroupId: null,
            providerCode: 'sber',
            amountKopecks: 50000,
            idempotencyKey: 'idem-x',
            correlationId: 'corr-y',
        );

        $array = $dto->toArray();

        $this->assertSame(1, $array['tenant_id']);
        $this->assertNull($array['business_group_id']);
        $this->assertSame('sber', $array['provider_code']);
        $this->assertSame(50000, $array['amount_kopecks']);
        $this->assertSame('idem-x', $array['idempotency_key']);
        $this->assertSame('corr-y', $array['correlation_id']);
        $this->assertFalse($array['is_hold']);
    }

    public function test_create_dto_to_audit_context(): void
    {
        $dto = new CreatePaymentRecordDto(
            tenantId: 1,
            businessGroupId: null,
            providerCode: 'tochka',
            amountKopecks: 75000,
            idempotencyKey: 'idem-z',
            correlationId: 'corr-z',
            isHold: true,
        );

        $context = $dto->toAuditContext();

        $this->assertArrayHasKey('provider_code', $context);
        $this->assertArrayHasKey('amount_kopecks', $context);
        $this->assertArrayHasKey('idempotency_key', $context);
        $this->assertArrayHasKey('is_hold', $context);
        $this->assertArrayHasKey('correlation_id', $context);
        $this->assertSame('corr-z', $context['correlation_id']);
    }

    public function test_create_dto_defaults(): void
    {
        $dto = new CreatePaymentRecordDto(
            tenantId: 1,
            businessGroupId: null,
            providerCode: 'sbp',
            amountKopecks: 1000,
            idempotencyKey: 'k',
            correlationId: 'c',
        );

        $this->assertFalse($dto->isHold);
        $this->assertSame('', $dto->description);
        $this->assertNull($dto->metadata);
    }

    public function test_create_dto_is_final_readonly(): void
    {
        $ref = new \ReflectionClass(CreatePaymentRecordDto::class);
        $this->assertTrue($ref->isFinal());
        $this->assertTrue($ref->isReadOnly());
    }

    // ─── UpdatePaymentRecordDto ──────────────────────────────────

    public function test_update_dto_constructor(): void
    {
        $dto = new UpdatePaymentRecordDto(
            paymentRecordId: 42,
            status: 'captured',
            correlationId: 'corr-update',
            providerPaymentId: 'pp-123',
            providerResponse: ['ok' => true],
            metadata: ['note' => 'test'],
        );

        $this->assertSame(42, $dto->paymentRecordId);
        $this->assertSame('captured', $dto->status);
        $this->assertSame('corr-update', $dto->correlationId);
        $this->assertSame('pp-123', $dto->providerPaymentId);
        $this->assertSame(['ok' => true], $dto->providerResponse);
        $this->assertSame(['note' => 'test'], $dto->metadata);
    }

    public function test_update_dto_to_array_includes_conditionals(): void
    {
        $dto = new UpdatePaymentRecordDto(
            paymentRecordId: 1,
            status: 'authorized',
            correlationId: 'c',
            providerPaymentId: 'pp-1',
            providerResponse: ['r' => 1],
            metadata: ['m' => 2],
        );

        $array = $dto->toArray();

        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('correlation_id', $array);
        $this->assertArrayHasKey('provider_payment_id', $array);
        $this->assertArrayHasKey('provider_response', $array);
        $this->assertArrayHasKey('metadata', $array);
    }

    public function test_update_dto_to_array_excludes_nulls(): void
    {
        $dto = new UpdatePaymentRecordDto(
            paymentRecordId: 1,
            status: 'failed',
            correlationId: 'c',
        );

        $array = $dto->toArray();

        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('correlation_id', $array);
        $this->assertArrayNotHasKey('provider_payment_id', $array);
        $this->assertArrayNotHasKey('provider_response', $array);
        $this->assertArrayNotHasKey('metadata', $array);
    }

    public function test_update_dto_to_audit_context(): void
    {
        $dto = new UpdatePaymentRecordDto(
            paymentRecordId: 7,
            status: 'refunded',
            correlationId: 'corr-ref',
            providerPaymentId: 'pp-ref',
        );

        $context = $dto->toAuditContext();

        $this->assertSame(7, $context['payment_record_id']);
        $this->assertSame('refunded', $context['new_status']);
        $this->assertSame('pp-ref', $context['provider_payment_id']);
        $this->assertSame('corr-ref', $context['correlation_id']);
    }

    public function test_update_dto_is_final_readonly(): void
    {
        $ref = new \ReflectionClass(UpdatePaymentRecordDto::class);
        $this->assertTrue($ref->isFinal());
        $this->assertTrue($ref->isReadOnly());
    }
}
