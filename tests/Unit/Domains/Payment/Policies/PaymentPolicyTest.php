<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Payment\Policies;

use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\PaymentRecord;
use App\Domains\Payment\Policies\PaymentRecordPolicy;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

/**
 * Unit-тесты для PaymentRecordPolicy.
 */
final class PaymentPolicyTest extends TestCase
{
    private PaymentRecordPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new PaymentRecordPolicy();
    }

    // ─── viewAny ─────────────────────────────────────────────────

    public function test_view_any_returns_true_for_same_tenant(): void
    {
        $user = $this->createUserStub(1, 10);
        $this->assertTrue($this->policy->viewAny($user));
    }

    // ─── view ────────────────────────────────────────────────────

    public function test_view_returns_true_for_same_tenant(): void
    {
        $user = $this->createUserStub(1, 10, null);
        $record = $this->createPaymentRecordStub(99, 10, null, 'pending');

        $this->assertTrue($this->policy->view($user, $record));
    }

    public function test_view_returns_false_for_different_tenant(): void
    {
        $user = $this->createUserStub(1, 10, null);
        $record = $this->createPaymentRecordStub(99, 20, null, 'pending');

        $this->assertFalse($this->policy->view($user, $record));
    }

    public function test_view_respects_b2b_business_group(): void
    {
        $user = $this->createUserStub(1, 10, 5);
        $recordSameGroup = $this->createPaymentRecordStub(1, 10, 5, 'pending');
        $recordDiffGroup = $this->createPaymentRecordStub(2, 10, 6, 'pending');

        $this->assertTrue($this->policy->view($user, $recordSameGroup));
        $this->assertFalse($this->policy->view($user, $recordDiffGroup));
    }

    // ─── create ──────────────────────────────────────────────────

    public function test_create_returns_true(): void
    {
        $user = $this->createUserStub(1, 10);
        $this->assertTrue($this->policy->create($user));
    }

    // ─── update ──────────────────────────────────────────────────

    public function test_update_returns_true_for_non_final_status(): void
    {
        $user = $this->createUserStub(1, 10, null);
        $record = $this->createPaymentRecordStub(1, 10, null, 'pending');

        $this->assertTrue($this->policy->update($user, $record));
    }

    public function test_update_returns_false_for_final_status(): void
    {
        $user = $this->createUserStub(1, 10, null);

        foreach (['captured', 'refunded', 'failed', 'cancelled'] as $status) {
            $record = $this->createPaymentRecordStub(1, 10, null, $status);
            $this->assertFalse(
                $this->policy->update($user, $record),
                "Final status '{$status}' must block update",
            );
        }
    }

    public function test_update_returns_false_for_different_tenant(): void
    {
        $user = $this->createUserStub(1, 10, null);
        $record = $this->createPaymentRecordStub(1, 20, null, 'pending');

        $this->assertFalse($this->policy->update($user, $record));
    }

    // ─── delete / restore / forceDelete ──────────────────────────

    public function test_delete_always_false(): void
    {
        $user = $this->createUserStub(1, 10, null);
        $record = $this->createPaymentRecordStub(1, 10, null, 'pending');

        $this->assertFalse($this->policy->delete($user, $record));
    }

    public function test_restore_always_false(): void
    {
        $user = $this->createUserStub(1, 10, null);
        $record = $this->createPaymentRecordStub(1, 10, null, 'pending');

        $this->assertFalse($this->policy->restore($user, $record));
    }

    public function test_force_delete_always_false(): void
    {
        $user = $this->createUserStub(1, 10, null);
        $record = $this->createPaymentRecordStub(1, 10, null, 'pending');

        $this->assertFalse($this->policy->forceDelete($user, $record));
    }

    // ─── Policy class structure ──────────────────────────────────

    public function test_policy_is_final(): void
    {
        $ref = new \ReflectionClass(PaymentRecordPolicy::class);
        $this->assertTrue($ref->isFinal());
    }

    // ─── Helpers ─────────────────────────────────────────────────

    private function createUserStub(int $id, int $tenantId, ?int $businessGroupId = null): User
    {
        $user = (new \ReflectionClass(User::class))->newInstanceWithoutConstructor();
        (new \ReflectionProperty(Model::class, 'attributes'))->setValue($user, [
            'id' => $id,
            'tenant_id' => $tenantId,
            'active_business_group_id' => $businessGroupId,
        ]);

        return $user;
    }

    private function createPaymentRecordStub(int $id, int $tenantId, ?int $businessGroupId, string $status): PaymentRecord
    {
        $record = (new \ReflectionClass(PaymentRecord::class))->newInstanceWithoutConstructor();
        (new \ReflectionProperty(Model::class, 'attributes'))->setValue($record, [
            'id' => $id,
            'tenant_id' => $tenantId,
            'business_group_id' => $businessGroupId,
            'status' => $status,
            'correlation_id' => 'stub-corr',
        ]);

        return $record;
    }
}
