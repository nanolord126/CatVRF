<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Finances\Policies;

use App\Domains\Finances\Models\FinanceRecord;
use App\Domains\Finances\Policies\FinanceRecordPolicy;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit-тесты политики доступа к финансовым записям.
 *
 * Покрытие: tenant-scoping, B2B-изоляция, forceDelete,
 * запрет удаления completed-записей.
 */
final class FinanceRecordPolicyTest extends TestCase
{
    private FinanceRecordPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new FinanceRecordPolicy();
    }

    private function makeUser(
        ?int $tenantId = 1,
        ?int $activeBusinessGroupId = null,
    ): User {
        $user = new User();
        $user->tenant_id = $tenantId;
        $user->active_business_group_id = $activeBusinessGroupId;

        return $user;
    }

    private function makeRecord(
        int $tenantId = 1,
        ?int $businessGroupId = null,
        string $status = 'draft',
    ): FinanceRecord {
        $record = new FinanceRecord();
        $record->id = 100;
        $record->tenant_id = $tenantId;
        $record->business_group_id = $businessGroupId;
        $record->status = $status;

        return $record;
    }

    // ──────────────────────────────────────
    //  viewAny
    // ──────────────────────────────────────

    #[Test]
    public function viewAny_allowed_when_user_has_tenant(): void
    {
        self::assertTrue($this->policy->viewAny($this->makeUser(tenantId: 1)));
    }

    #[Test]
    public function viewAny_denied_when_no_tenant(): void
    {
        self::assertFalse($this->policy->viewAny($this->makeUser(tenantId: null)));
    }

    // ──────────────────────────────────────
    //  view
    // ──────────────────────────────────────

    #[Test]
    public function view_allowed_same_tenant(): void
    {
        $response = $this->policy->view(
            $this->makeUser(tenantId: 5),
            $this->makeRecord(tenantId: 5),
        );

        self::assertTrue($response->allowed());
    }

    #[Test]
    public function view_denied_different_tenant(): void
    {
        $response = $this->policy->view(
            $this->makeUser(tenantId: 1),
            $this->makeRecord(tenantId: 2),
        );

        self::assertFalse($response->allowed());
        self::assertStringContainsString('другого тенанта', $response->message());
    }

    // ──────────────────────────────────────
    //  create
    // ──────────────────────────────────────

    #[Test]
    public function create_allowed_with_tenant(): void
    {
        self::assertTrue($this->policy->create($this->makeUser(tenantId: 1)));
    }

    #[Test]
    public function create_denied_without_tenant(): void
    {
        self::assertFalse($this->policy->create($this->makeUser(tenantId: null)));
    }

    // ──────────────────────────────────────
    //  update
    // ──────────────────────────────────────

    #[Test]
    public function update_allowed_same_tenant(): void
    {
        $response = $this->policy->update(
            $this->makeUser(tenantId: 3),
            $this->makeRecord(tenantId: 3),
        );

        self::assertTrue($response->allowed());
    }

    #[Test]
    public function update_denied_different_tenant(): void
    {
        $response = $this->policy->update(
            $this->makeUser(tenantId: 1),
            $this->makeRecord(tenantId: 99),
        );

        self::assertFalse($response->allowed());
    }

    // ──────────────────────────────────────
    //  delete
    // ──────────────────────────────────────

    #[Test]
    public function delete_allowed_same_tenant_not_completed(): void
    {
        $response = $this->policy->delete(
            $this->makeUser(tenantId: 1),
            $this->makeRecord(tenantId: 1, status: 'draft'),
        );

        self::assertTrue($response->allowed());
    }

    #[Test]
    public function delete_denied_different_tenant(): void
    {
        $response = $this->policy->delete(
            $this->makeUser(tenantId: 1),
            $this->makeRecord(tenantId: 2),
        );

        self::assertFalse($response->allowed());
    }

    #[Test]
    public function delete_denied_completed_record(): void
    {
        $response = $this->policy->delete(
            $this->makeUser(tenantId: 1),
            $this->makeRecord(tenantId: 1, status: 'completed'),
        );

        self::assertFalse($response->allowed());
        self::assertStringContainsString('завершённые', $response->message());
    }

    // ──────────────────────────────────────
    //  restore
    // ──────────────────────────────────────

    #[Test]
    public function restore_allowed_same_tenant(): void
    {
        $response = $this->policy->restore(
            $this->makeUser(tenantId: 7),
            $this->makeRecord(tenantId: 7),
        );

        self::assertTrue($response->allowed());
    }

    #[Test]
    public function restore_denied_different_tenant(): void
    {
        $response = $this->policy->restore(
            $this->makeUser(tenantId: 1),
            $this->makeRecord(tenantId: 8),
        );

        self::assertFalse($response->allowed());
    }

    // ──────────────────────────────────────
    //  forceDelete — всегда запрещено
    // ──────────────────────────────────────

    #[Test]
    public function forceDelete_always_denied(): void
    {
        self::assertFalse(
            $this->policy->forceDelete(
                $this->makeUser(tenantId: 1),
                $this->makeRecord(tenantId: 1),
            ),
        );
    }

    #[Test]
    public function forceDelete_denied_even_for_same_tenant(): void
    {
        self::assertFalse(
            $this->policy->forceDelete(
                $this->makeUser(tenantId: 42),
                $this->makeRecord(tenantId: 42),
            ),
        );
    }

    // ──────────────────────────────────────
    //  B2B-изоляция (business_group)
    // ──────────────────────────────────────

    #[Test]
    public function b2b_view_allowed_same_business_group(): void
    {
        $response = $this->policy->view(
            $this->makeUser(tenantId: 1, activeBusinessGroupId: 10),
            $this->makeRecord(tenantId: 1, businessGroupId: 10),
        );

        self::assertTrue($response->allowed());
    }

    #[Test]
    public function b2b_view_denied_different_business_group(): void
    {
        $response = $this->policy->view(
            $this->makeUser(tenantId: 1, activeBusinessGroupId: 10),
            $this->makeRecord(tenantId: 1, businessGroupId: 20),
        );

        self::assertFalse($response->allowed());
        self::assertStringContainsString('филиала', $response->message());
    }

    #[Test]
    public function b2b_record_without_group_accessible_to_all(): void
    {
        $response = $this->policy->view(
            $this->makeUser(tenantId: 1, activeBusinessGroupId: 10),
            $this->makeRecord(tenantId: 1, businessGroupId: null),
        );

        self::assertTrue($response->allowed());
    }

    #[Test]
    public function b2c_user_can_access_grouped_record(): void
    {
        $response = $this->policy->view(
            $this->makeUser(tenantId: 1, activeBusinessGroupId: null),
            $this->makeRecord(tenantId: 1, businessGroupId: 5),
        );

        self::assertTrue($response->allowed());
    }

    #[Test]
    public function b2b_delete_denied_different_business_group(): void
    {
        $response = $this->policy->delete(
            $this->makeUser(tenantId: 1, activeBusinessGroupId: 10),
            $this->makeRecord(tenantId: 1, businessGroupId: 99),
        );

        self::assertFalse($response->allowed());
    }

    #[Test]
    public function b2b_update_denied_different_business_group(): void
    {
        $response = $this->policy->update(
            $this->makeUser(tenantId: 1, activeBusinessGroupId: 10),
            $this->makeRecord(tenantId: 1, businessGroupId: 77),
        );

        self::assertFalse($response->allowed());
    }
}
