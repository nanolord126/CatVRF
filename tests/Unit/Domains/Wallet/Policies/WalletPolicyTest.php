<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Wallet\Policies;

use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Policies\WalletPolicy;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

final class WalletPolicyTest extends TestCase
{
    private WalletPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new WalletPolicy();
    }

    // ─── viewAny ─────────────────────────────────────────────────────

    public function test_view_any_returns_true_when_user_has_tenant(): void
    {
        $user = $this->createUserStub(tenantId: 10);
        $this->assertTrue($this->policy->viewAny($user));
    }

    public function test_view_any_returns_false_when_user_has_no_tenant(): void
    {
        $user = $this->createUserStub(tenantId: null);
        $this->assertFalse($this->policy->viewAny($user));
    }

    // ─── view ────────────────────────────────────────────────────────

    public function test_view_returns_true_for_same_tenant(): void
    {
        $user = $this->createUserStub(tenantId: 10);
        $wallet = $this->createWalletStub(tenantId: 10, businessGroupId: null);

        $this->assertTrue($this->policy->view($user, $wallet));
    }

    public function test_view_returns_false_for_different_tenant(): void
    {
        $user = $this->createUserStub(tenantId: 10);
        $wallet = $this->createWalletStub(tenantId: 20, businessGroupId: null);

        $this->assertFalse($this->policy->view($user, $wallet));
    }

    public function test_view_returns_true_for_same_business_group(): void
    {
        $user = $this->createUserStub(tenantId: 10, businessGroupId: 5);
        $wallet = $this->createWalletStub(tenantId: 10, businessGroupId: 5);

        $this->assertTrue($this->policy->view($user, $wallet));
    }

    public function test_view_returns_false_for_different_business_group(): void
    {
        $user = $this->createUserStub(tenantId: 10, businessGroupId: 5);
        $wallet = $this->createWalletStub(tenantId: 10, businessGroupId: 7);

        $this->assertFalse($this->policy->view($user, $wallet));
    }

    // ─── create ──────────────────────────────────────────────────────

    public function test_create_returns_true_when_has_tenant(): void
    {
        $user = $this->createUserStub(tenantId: 10);
        $this->assertTrue($this->policy->create($user));
    }

    // ─── delete ──────────────────────────────────────────────────────

    public function test_delete_returns_true_when_balance_zero(): void
    {
        $user = $this->createUserStub(tenantId: 10);
        $wallet = $this->createWalletStub(tenantId: 10, businessGroupId: null, balance: 0, hold: 0);

        $this->assertTrue($this->policy->delete($user, $wallet));
    }

    public function test_delete_returns_false_when_has_balance(): void
    {
        $user = $this->createUserStub(tenantId: 10);
        $wallet = $this->createWalletStub(tenantId: 10, businessGroupId: null, balance: 5000, hold: 0);

        $this->assertFalse($this->policy->delete($user, $wallet));
    }

    public function test_delete_returns_false_when_has_hold(): void
    {
        $user = $this->createUserStub(tenantId: 10);
        $wallet = $this->createWalletStub(tenantId: 10, businessGroupId: null, balance: 0, hold: 1000);

        $this->assertFalse($this->policy->delete($user, $wallet));
    }

    // ─── forceDelete ─────────────────────────────────────────────────

    public function test_force_delete_always_returns_false(): void
    {
        $user = $this->createUserStub(tenantId: 10);
        $wallet = $this->createWalletStub(tenantId: 10, businessGroupId: null);

        $this->assertFalse($this->policy->forceDelete($user, $wallet));
    }

    // ─── Helpers ─────────────────────────────────────────────────────

    /**
     * Создаёт stub User через Reflection (обход final class).
     */
    private function createUserStub(?int $tenantId, ?int $businessGroupId = null): User
    {
        $user = (new \ReflectionClass(User::class))->newInstanceWithoutConstructor();

        $prop = new \ReflectionProperty(Model::class, 'attributes');
        $prop->setValue($user, [
            'tenant_id' => $tenantId,
            'active_business_group_id' => $businessGroupId,
            'business_group_id' => $businessGroupId,
        ]);

        return $user;
    }

    /**
     * Создаёт stub Wallet через Reflection (обход final class).
     */
    private function createWalletStub(int $tenantId, ?int $businessGroupId, int $balance = 0, int $hold = 0): Wallet
    {
        $wallet = (new \ReflectionClass(Wallet::class))->newInstanceWithoutConstructor();

        $prop = new \ReflectionProperty(Model::class, 'attributes');
        $prop->setValue($wallet, [
            'tenant_id' => $tenantId,
            'business_group_id' => $businessGroupId,
            'current_balance' => $balance,
            'hold_amount' => $hold,
        ]);

        return $wallet;
    }
}
