<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Wallet\Events;

use App\Domains\Wallet\Events\WalletCreated;
use App\Domains\Wallet\Events\WalletUpdated;
use App\Domains\Wallet\Models\Wallet;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

final class WalletEventsTest extends TestCase
{
    public function test_wallet_created_stores_properties(): void
    {
        $wallet = $this->createWalletStub(1, 10, 5);

        $event = new WalletCreated(
            wallet: $wallet,
            correlationId: 'corr-evt-1',
            userId: 42,
        );

        $this->assertSame($wallet, $event->wallet);
        $this->assertSame('corr-evt-1', $event->correlationId);
        $this->assertSame(42, $event->userId);
    }

    public function test_wallet_created_to_audit_context(): void
    {
        $wallet = $this->createWalletStub(1, 10, null);

        $event = new WalletCreated(
            wallet: $wallet,
            correlationId: 'corr-evt-2',
            userId: null,
        );

        $ctx = $event->toAuditContext();

        $this->assertSame(1, $ctx['wallet_id']);
        $this->assertSame('corr-evt-2', $ctx['correlation_id']);
        $this->assertNull($ctx['user_id']);
    }

    public function test_wallet_created_get_tenant_id(): void
    {
        $wallet = $this->createWalletStub(1, 10, null);

        $event = new WalletCreated($wallet, 'corr', null);

        $this->assertSame(10, $event->getTenantId());
    }

    public function test_wallet_created_get_business_group_id(): void
    {
        $wallet = $this->createWalletStub(1, 10, 5);

        $event = new WalletCreated($wallet, 'corr', null);

        $this->assertSame(5, $event->getBusinessGroupId());
    }

    // ─── WalletUpdated ───────────────────────────────────────────────

    public function test_wallet_updated_stores_properties(): void
    {
        $wallet = $this->createWalletStub(1, 10, null);

        $event = new WalletUpdated(
            wallet: $wallet,
            correlationId: 'upd-corr-1',
            userId: 42,
            oldValues: ['current_balance' => 0],
            newValues: ['current_balance' => 50000],
        );

        $this->assertSame(['current_balance' => 0], $event->oldValues);
        $this->assertSame(['current_balance' => 50000], $event->newValues);
    }

    public function test_wallet_updated_has_changed(): void
    {
        $wallet = $this->createWalletStub(1, 10, null);

        $event = new WalletUpdated(
            wallet: $wallet,
            correlationId: 'upd-corr-2',
            userId: null,
            oldValues: ['current_balance' => 0],
            newValues: ['current_balance' => 50000],
        );

        $this->assertTrue($event->hasChanged('current_balance'));
        $this->assertFalse($event->hasChanged('hold_amount'));
    }

    public function test_wallet_updated_to_audit_context_includes_old_new(): void
    {
        $wallet = $this->createWalletStub(1, 10, null);

        $event = new WalletUpdated(
            wallet: $wallet,
            correlationId: 'upd-corr-3',
            userId: 42,
            oldValues: ['current_balance' => 100],
            newValues: ['current_balance' => 200],
        );

        $ctx = $event->toAuditContext();

        $this->assertArrayHasKey('old_values', $ctx);
        $this->assertArrayHasKey('new_values', $ctx);
        $this->assertSame(100, $ctx['old_values']['current_balance']);
        $this->assertSame(200, $ctx['new_values']['current_balance']);
    }

    // ─── Helpers ─────────────────────────────────────────────────────

    /**
     * Создаёт stub Wallet через Reflection (обход final class).
     * newInstanceWithoutConstructor() не вызывает __construct/booted(),
     * но PHP 8.2 инициализирует default-значения свойств (protected $attributes = []).
     */
    private function createWalletStub(int $id, int $tenantId, ?int $businessGroupId): Wallet
    {
        $wallet = (new \ReflectionClass(Wallet::class))->newInstanceWithoutConstructor();

        $prop = new \ReflectionProperty(Model::class, 'attributes');
        $prop->setValue($wallet, [
            'id' => $id,
            'tenant_id' => $tenantId,
            'business_group_id' => $businessGroupId,
        ]);

        return $wallet;
    }
}
