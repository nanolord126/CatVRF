<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Finances\Events;

use App\Domains\Finances\Events\FinanceRecordUpdated;
use App\Domains\Finances\Models\FinanceRecord;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit-тесты события FinanceRecordUpdated.
 *
 * Покрытие: конструктор, toAuditContext, getTenantId,
 * getBusinessGroupId, hasChanged, oldValues/newValues.
 */
final class FinanceRecordUpdatedTest extends TestCase
{
    private function makeRecord(
        int $id = 1,
        int $tenantId = 10,
        ?int $businessGroupId = null,
    ): FinanceRecord {
        $record = new FinanceRecord();
        $record->id = $id;
        $record->tenant_id = $tenantId;
        $record->business_group_id = $businessGroupId;

        return $record;
    }

    #[Test]
    public function it_stores_all_properties(): void
    {
        $record = $this->makeRecord();
        $old = ['amount' => 100];
        $new = ['amount' => 200];

        $event = new FinanceRecordUpdated($record, 'corr-upd', $old, $new, 77);

        self::assertSame($record, $event->financeRecord);
        self::assertSame('corr-upd', $event->correlationId);
        self::assertSame($old, $event->oldValues);
        self::assertSame($new, $event->newValues);
        self::assertSame(77, $event->userId);
    }

    #[Test]
    public function defaults_are_empty_arrays_and_null_user(): void
    {
        $event = new FinanceRecordUpdated(
            $this->makeRecord(),
            'corr-def',
        );

        self::assertSame([], $event->oldValues);
        self::assertSame([], $event->newValues);
        self::assertNull($event->userId);
    }

    #[Test]
    public function toAuditContext_returns_complete_structure(): void
    {
        $record = $this->makeRecord(id: 33, tenantId: 5, businessGroupId: 8);
        $old = ['status' => 'draft', 'amount' => 100];
        $new = ['status' => 'completed', 'amount' => 200];

        $event = new FinanceRecordUpdated($record, 'aud-2', $old, $new, 11);
        $ctx = $event->toAuditContext();

        self::assertSame(33, $ctx['finance_record_id']);
        self::assertSame(5, $ctx['tenant_id']);
        self::assertSame(8, $ctx['business_group_id']);
        self::assertSame($old, $ctx['old_values']);
        self::assertSame($new, $ctx['new_values']);
        self::assertSame('aud-2', $ctx['correlation_id']);
        self::assertSame(11, $ctx['user_id']);
    }

    #[Test]
    public function getTenantId_returns_record_tenant(): void
    {
        $event = new FinanceRecordUpdated(
            $this->makeRecord(tenantId: 99),
            'tid',
        );

        self::assertSame(99, $event->getTenantId());
    }

    #[Test]
    public function getBusinessGroupId_returns_record_group(): void
    {
        $event = new FinanceRecordUpdated(
            $this->makeRecord(businessGroupId: 42),
            'bg',
        );

        self::assertSame(42, $event->getBusinessGroupId());
    }

    #[Test]
    public function getBusinessGroupId_null_for_b2c(): void
    {
        $event = new FinanceRecordUpdated(
            $this->makeRecord(businessGroupId: null),
            'bg-null',
        );

        self::assertNull($event->getBusinessGroupId());
    }

    // ──────────────────────────────────────
    //  hasChanged
    // ──────────────────────────────────────

    #[Test]
    public function hasChanged_true_when_field_in_newValues(): void
    {
        $event = new FinanceRecordUpdated(
            $this->makeRecord(),
            'hc-1',
            oldValues: ['status' => 'draft'],
            newValues: ['status' => 'completed', 'amount' => 300],
        );

        self::assertTrue($event->hasChanged('status'));
        self::assertTrue($event->hasChanged('amount'));
    }

    #[Test]
    public function hasChanged_false_when_field_not_in_newValues(): void
    {
        $event = new FinanceRecordUpdated(
            $this->makeRecord(),
            'hc-2',
            oldValues: [],
            newValues: ['status' => 'completed'],
        );

        self::assertFalse($event->hasChanged('amount'));
        self::assertFalse($event->hasChanged('currency'));
        self::assertFalse($event->hasChanged('description'));
    }

    #[Test]
    public function hasChanged_false_when_newValues_empty(): void
    {
        $event = new FinanceRecordUpdated(
            $this->makeRecord(),
            'hc-empty',
        );

        self::assertFalse($event->hasChanged('status'));
    }

    #[Test]
    public function toAuditContext_contains_all_required_keys(): void
    {
        $event = new FinanceRecordUpdated(
            $this->makeRecord(),
            'keys',
            ['a' => 1],
            ['b' => 2],
            5,
        );

        $keys = array_keys($event->toAuditContext());

        self::assertContains('finance_record_id', $keys);
        self::assertContains('tenant_id', $keys);
        self::assertContains('business_group_id', $keys);
        self::assertContains('old_values', $keys);
        self::assertContains('new_values', $keys);
        self::assertContains('correlation_id', $keys);
        self::assertContains('user_id', $keys);
    }
}
