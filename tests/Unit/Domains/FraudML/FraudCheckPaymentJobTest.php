<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\FraudML;

use App\Domains\FraudML\DTOs\PaymentFraudMLDto;
use App\Jobs\FraudCheckPaymentJob;
use App\Domains\FraudML\Services\PaymentFraudMLService;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Illuminate\Support\Str;

final class FraudCheckPaymentJobTest extends TestCase
{
    public function test_job_has_unique_id_based_on_idempotency_key(): void
    {
        $dto = new PaymentFraudMLDto(
            tenant_id: 1,
            user_id: 1,
            operation_type: 'payment',
            amount_kopecks: 15000,
            ip_address: '127.0.0.1',
            device_fingerprint: 'test-device',
            correlation_id: 'test-correlation',
            idempotency_key: 'unique-test-key',
            vertical_code: 'medical',
        );

        $job = new FraudCheckPaymentJob($dto);

        $this->assertEquals('unique-test-key', $job->uniqueId());
    }

    public function test_job_uses_correct_queue(): void
    {
        $dto = new PaymentFraudMLDto(
            tenant_id: 1,
            user_id: 1,
            operation_type: 'payment',
            amount_kopecks: 15000,
            ip_address: '127.0.0.1',
            device_fingerprint: 'test-device',
            correlation_id: 'test-correlation',
            idempotency_key: 'queue-test-key',
            vertical_code: 'medical',
        );

        $job = new FraudCheckPaymentJob($dto);

        $this->assertEquals('fraud-check-payment', $job->queue);
    }

    public function test_job_has_correct_timeout(): void
    {
        $dto = new PaymentFraudMLDto(
            tenant_id: 1,
            user_id: 1,
            operation_type: 'payment',
            amount_kopecks: 15000,
            ip_address: '127.0.0.1',
            device_fingerprint: 'test-device',
            correlation_id: 'test-correlation',
            idempotency_key: 'timeout-test-key',
            vertical_code: 'medical',
        );

        $job = new FraudCheckPaymentJob($dto);

        $this->assertEquals(30, $job->timeout);
    }

    public function test_job_has_correct_retry_configuration(): void
    {
        $dto = new PaymentFraudMLDto(
            tenant_id: 1,
            user_id: 1,
            operation_type: 'payment',
            amount_kopecks: 15000,
            ip_address: '127.0.0.1',
            device_fingerprint: 'test-device',
            correlation_id: 'test-correlation',
            idempotency_key: 'retry-test-key',
            vertical_code: 'medical',
        );

        $job = new FraudCheckPaymentJob($dto);

        $this->assertEquals(3, $job->tries);
        $this->assertEquals([5, 10, 20], $job->backoff);
    }

    public function test_job_tags_include_vertical_and_user(): void
    {
        $dto = new PaymentFraudMLDto(
            tenant_id: 123,
            user_id: 456,
            operation_type: 'payment',
            amount_kopecks: 15000,
            ip_address: '127.0.0.1',
            device_fingerprint: 'test-device',
            correlation_id: 'test-correlation',
            idempotency_key: 'tags-test-key',
            vertical_code: 'food',
        );

        $job = new FraudCheckPaymentJob($dto);

        $tags = $job->tags();

        $this->assertContains('fraud-check:payment', $tags);
        $this->assertContains('vertical:food', $tags);
        $this->assertContains('tenant:123', $tags);
        $this->assertContains('user:456', $tags);
    }

    public function test_job_processes_dto_correctly(): void
    {
        $dto = new PaymentFraudMLDto(
            tenant_id: 1,
            user_id: 1,
            operation_type: 'payment',
            amount_kopecks: 15000,
            ip_address: '127.0.0.1',
            device_fingerprint: 'test-device',
            correlation_id: 'test-correlation',
            idempotency_key: 'process-test-key',
            vertical_code: 'medical',
            urgency_level: 'high',
            is_emergency_payment: true,
        );

        $job = new FraudCheckPaymentJob($dto);

        // Access private property via reflection
        $reflection = new \ReflectionClass($job);
        $property = $reflection->getProperty('dto');
        $property->setAccessible(true);
        $jobDto = $property->getValue($job);

        $this->assertEquals(1, $jobDto->tenant_id);
        $this->assertEquals(1, $jobDto->user_id);
        $this->assertEquals('medical', $jobDto->vertical_code);
        $this->assertEquals('high', $jobDto->urgency_level);
        $this->assertTrue($jobDto->is_emergency_payment);
    }
}
