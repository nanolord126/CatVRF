<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Payment\Events;

use App\Domains\Payment\Events\PaymentRecordCreated;
use App\Domains\Payment\Events\PaymentRecordUpdated;
use App\Domains\Payment\Models\PaymentRecord;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

/**
 * Unit-тесты для Payment Events.
 */
final class PaymentEventsTest extends TestCase
{
    // ─── PaymentRecordCreated ────────────────────────────────────

    public function test_created_event_has_public_readonly_properties(): void
    {
        $ref = new \ReflectionClass(PaymentRecordCreated::class);

        $expected = ['paymentRecord', 'correlationId', 'userId'];

        foreach ($expected as $name) {
            $this->assertTrue($ref->hasProperty($name), "Missing property {$name}");
            $prop = $ref->getProperty($name);
            $this->assertTrue($prop->isPublic(), "Property {$name} must be public");
            $this->assertTrue($prop->isReadOnly(), "Property {$name} must be readonly");
        }
    }

    public function test_created_event_stores_data(): void
    {
        $record = $this->createPaymentRecordStub(1, 10, 'tinkoff', 'pending', 50000);

        $event = new PaymentRecordCreated(
            paymentRecord: $record,
            correlationId: 'corr-123',
            userId: 42,
        );

        $this->assertSame($record, $event->paymentRecord);
        $this->assertSame('corr-123', $event->correlationId);
        $this->assertSame(42, $event->userId);
    }

    public function test_created_event_to_audit_context(): void
    {
        $record = $this->createPaymentRecordStub(5, 10, 'sber', 'captured', 100000);

        $event = new PaymentRecordCreated($record, 'corr-a', 7);
        $ctx = $event->toAuditContext();

        $this->assertArrayHasKey('event', $ctx);
        $this->assertArrayHasKey('payment_record_id', $ctx);
        $this->assertArrayHasKey('correlation_id', $ctx);
        $this->assertSame('payment_record_created', $ctx['event']);
        $this->assertSame('corr-a', $ctx['correlation_id']);
    }

    public function test_created_event_get_tenant_id(): void
    {
        $record = $this->createPaymentRecordStub(99, 10, 'sbp', 'pending', 1000);
        $event = new PaymentRecordCreated($record, 'c');

        $this->assertSame(10, $event->getTenantId());
    }

    // ─── PaymentRecordUpdated ────────────────────────────────────

    public function test_updated_event_has_public_readonly_properties(): void
    {
        $ref = new \ReflectionClass(PaymentRecordUpdated::class);

        $expected = ['paymentRecord', 'correlationId', 'oldValues', 'newValues'];

        foreach ($expected as $name) {
            $this->assertTrue($ref->hasProperty($name), "Missing property {$name}");
            $prop = $ref->getProperty($name);
            $this->assertTrue($prop->isPublic(), "Property {$name} must be public");
            $this->assertTrue($prop->isReadOnly(), "Property {$name} must be readonly");
        }
    }

    public function test_updated_event_stores_old_and_new_values(): void
    {
        $record = $this->createPaymentRecordStub(1, 10, 'tinkoff', 'captured', 50000);

        $event = new PaymentRecordUpdated(
            paymentRecord: $record,
            correlationId: 'corr-up',
            oldValues: ['status' => 'pending'],
            newValues: ['status' => 'captured'],
            userId: 3,
        );

        $this->assertSame(['status' => 'pending'], $event->oldValues);
        $this->assertSame(['status' => 'captured'], $event->newValues);
        $this->assertSame(3, $event->userId);
    }

    public function test_updated_event_has_changed(): void
    {
        $record = $this->createPaymentRecordStub(1, 10, 'tinkoff', 'authorized', 50000);

        $event = new PaymentRecordUpdated(
            paymentRecord: $record,
            correlationId: 'c',
            newValues: ['status' => 'authorized'],
        );

        $this->assertTrue($event->hasChanged('status'));
        $this->assertFalse($event->hasChanged('amount_kopecks'));
    }

    // ─── Helpers ─────────────────────────────────────────────────

    private function createPaymentRecordStub(int $id, int $tenantId, string $provider, string $status, int $amount): PaymentRecord
    {
        $record = (new \ReflectionClass(PaymentRecord::class))->newInstanceWithoutConstructor();
        (new \ReflectionProperty(Model::class, 'attributes'))->setValue($record, [
            'id' => $id,
            'tenant_id' => $tenantId,
            'business_group_id' => null,
            'provider_code' => $provider,
            'status' => $status,
            'amount_kopecks' => $amount,
            'correlation_id' => 'stub-corr',
        ]);

        return $record;
    }
}
