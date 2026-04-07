<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Finances\Events;

use App\Domains\Finances\Events\FinanceRecordCreated;
use App\Domains\Finances\Models\FinanceRecord;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit-тесты события FinanceRecordCreated.
 *
 * Покрытие: конструктор, toAuditContext, getTenantId,
 * getBusinessGroupId, nullable userId.
 */
final class FinanceRecordCreatedTest extends TestCase
{
    private function makeRecord(
        int $id = 1,
        int $tenantId = 10,
        ?int $businessGroupId = null,
        string $type = 'deposit',
        int $amount = 500000,
    ): FinanceRecord {
        $record = new FinanceRecord();
        $record->id = $id;
        $record->tenant_id = $tenantId;
        $record->business_group_id = $businessGroupId;
        $record->type = $type;
        $record->amount = $amount;

        return $record;
    }

    #[Test]
    public function it_stores_readonly_properties(): void
    {
        $record = $this->makeRecord();
        $event = new FinanceRecordCreated($record, 'corr-123', 42);

        self::assertSame($record, $event->financeRecord);
        self::assertSame('corr-123', $event->correlationId);
        self::assertSame(42, $event->userId);
    }

    #[Test]
    public function userId_defaults_to_null(): void
    {
        $event = new FinanceRecordCreated(
            $this->makeRecord(),
            'corr-null',
        );

        self::assertNull($event->userId);
    }

    #[Test]
    public function toAuditContext_returns_complete_structure(): void
    {
        $record = $this->makeRecord(
            id: 55,
            tenantId: 7,
            businessGroupId: 3,
            type: 'commission',
            amount: 100000,
        );

        $event = new FinanceRecordCreated($record, 'audit-1', 99);
        $ctx = $event->toAuditContext();

        self::assertSame(55, $ctx['finance_record_id']);
        self::assertSame(7, $ctx['tenant_id']);
        self::assertSame(3, $ctx['business_group_id']);
        self::assertSame('commission', $ctx['type']);
        self::assertSame(100000, $ctx['amount']);
        self::assertSame('audit-1', $ctx['correlation_id']);
        self::assertSame(99, $ctx['user_id']);
    }

    #[Test]
    public function toAuditContext_with_null_business_group(): void
    {
        $record = $this->makeRecord(businessGroupId: null);
        $event = new FinanceRecordCreated($record, 'corr-b2c');

        $ctx = $event->toAuditContext();

        self::assertNull($ctx['business_group_id']);
        self::assertNull($ctx['user_id']);
    }

    #[Test]
    public function getTenantId_returns_record_tenant(): void
    {
        $record = $this->makeRecord(tenantId: 42);
        $event = new FinanceRecordCreated($record, 'tid-1');

        self::assertSame(42, $event->getTenantId());
    }

    #[Test]
    public function getBusinessGroupId_returns_record_group(): void
    {
        $record = $this->makeRecord(businessGroupId: 15);
        $event = new FinanceRecordCreated($record, 'bg-1');

        self::assertSame(15, $event->getBusinessGroupId());
    }

    #[Test]
    public function getBusinessGroupId_null_for_b2c(): void
    {
        $record = $this->makeRecord(businessGroupId: null);
        $event = new FinanceRecordCreated($record, 'bg-null');

        self::assertNull($event->getBusinessGroupId());
    }

    #[Test]
    public function toAuditContext_contains_all_required_keys(): void
    {
        $event = new FinanceRecordCreated(
            $this->makeRecord(),
            'keys-check',
            1,
        );

        $keys = array_keys($event->toAuditContext());

        self::assertContains('finance_record_id', $keys);
        self::assertContains('tenant_id', $keys);
        self::assertContains('business_group_id', $keys);
        self::assertContains('type', $keys);
        self::assertContains('amount', $keys);
        self::assertContains('correlation_id', $keys);
        self::assertContains('user_id', $keys);
    }
}
