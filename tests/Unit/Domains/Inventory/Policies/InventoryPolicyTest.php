<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Inventory\Policies;

use App\Domains\Inventory\Models\InventoryCheck;
use App\Domains\Inventory\Policies\InventoryCheckPolicy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

/**
 * Тесты Policy для InventoryCheck.
 *
 * Проверяем: final, strict_types, tenant-scoped авторизация, no facades.
 */
#[CoversClass(InventoryCheckPolicy::class)]
final class InventoryPolicyTest extends TestCase
{
    /* ================================================================== */
    /*  Structural                                                         */
    /* ================================================================== */

    #[Test]
    public function policy_is_final(): void
    {
        self::assertTrue((new ReflectionClass(InventoryCheckPolicy::class))->isFinal());
    }

    #[Test]
    public function policy_has_strict_types(): void
    {
        $ref  = new ReflectionClass(InventoryCheckPolicy::class);
        $code = (string) file_get_contents((string) $ref->getFileName());
        self::assertStringContainsString('declare(strict_types=1);', $code);
    }

    #[Test]
    public function policy_has_no_facades(): void
    {
        $ref  = new ReflectionClass(InventoryCheckPolicy::class);
        $code = (string) file_get_contents((string) $ref->getFileName());
        self::assertStringNotContainsString('use Illuminate\Support\Facades\\', $code);
    }

    /* ================================================================== */
    /*  Methods exist                                                      */
    /* ================================================================== */

    #[Test]
    public function policy_has_required_methods(): void
    {
        $expected = ['viewAny', 'view', 'create', 'update', 'delete', 'restore', 'forceDelete'];

        foreach ($expected as $method) {
            self::assertTrue(
                method_exists(InventoryCheckPolicy::class, $method),
                "InventoryCheckPolicy must have method '{$method}'",
            );
        }
    }

    /* ================================================================== */
    /*  viewAny: tenant check                                              */
    /* ================================================================== */

    #[Test]
    public function view_any_allows_user_with_tenant(): void
    {
        $policy = new InventoryCheckPolicy();
        $user   = $this->buildUser(tenantId: 1);

        self::assertTrue($policy->viewAny($user));
    }

    #[Test]
    public function view_any_denies_user_without_tenant(): void
    {
        $policy = new InventoryCheckPolicy();
        $user   = $this->buildUser(tenantId: null);

        self::assertFalse($policy->viewAny($user));
    }

    /* ================================================================== */
    /*  view: tenant match                                                 */
    /* ================================================================== */

    #[Test]
    public function view_allows_matching_tenant(): void
    {
        $policy = new InventoryCheckPolicy();
        $user   = $this->buildUser(tenantId: 5);
        $check  = $this->buildCheck(tenantId: 5);

        self::assertTrue($policy->view($user, $check));
    }

    #[Test]
    public function view_denies_non_matching_tenant(): void
    {
        $policy = new InventoryCheckPolicy();
        $user   = $this->buildUser(tenantId: 5);
        $check  = $this->buildCheck(tenantId: 99);

        self::assertFalse($policy->view($user, $check));
    }

    /* ================================================================== */
    /*  create: tenant check                                               */
    /* ================================================================== */

    #[Test]
    public function create_allows_user_with_tenant(): void
    {
        $policy = new InventoryCheckPolicy();
        $user   = $this->buildUser(tenantId: 1);

        self::assertTrue($policy->create($user));
    }

    #[Test]
    public function create_denies_user_without_tenant(): void
    {
        $policy = new InventoryCheckPolicy();
        $user   = $this->buildUser(tenantId: null);

        self::assertFalse($policy->create($user));
    }

    /* ================================================================== */
    /*  update: tenant match                                               */
    /* ================================================================== */

    #[Test]
    public function update_allows_matching_tenant(): void
    {
        $policy = new InventoryCheckPolicy();
        $user   = $this->buildUser(tenantId: 7);
        $check  = $this->buildCheck(tenantId: 7);

        self::assertTrue($policy->update($user, $check));
    }

    #[Test]
    public function update_denies_non_matching_tenant(): void
    {
        $policy = new InventoryCheckPolicy();
        $user   = $this->buildUser(tenantId: 7);
        $check  = $this->buildCheck(tenantId: 42);

        self::assertFalse($policy->update($user, $check));
    }

    /* ================================================================== */
    /*  delete: tenant match                                               */
    /* ================================================================== */

    #[Test]
    public function delete_allows_matching_tenant(): void
    {
        $policy = new InventoryCheckPolicy();
        $user   = $this->buildUser(tenantId: 3);
        $check  = $this->buildCheck(tenantId: 3);

        self::assertTrue($policy->delete($user, $check));
    }

    #[Test]
    public function delete_denies_non_matching_tenant(): void
    {
        $policy = new InventoryCheckPolicy();
        $user   = $this->buildUser(tenantId: 3);
        $check  = $this->buildCheck(tenantId: 11);

        self::assertFalse($policy->delete($user, $check));
    }

    /* ================================================================== */
    /*  Helpers                                                            */
    /* ================================================================== */

    private function buildUser(?int $tenantId): object
    {
        return new class ($tenantId) {
            public function __construct(public readonly ?int $tenant_id) {}
        };
    }

    private function buildCheck(int $tenantId): InventoryCheck
    {
        $ref   = new ReflectionClass(InventoryCheck::class);
        $check = $ref->newInstanceWithoutConstructor();

        $prop = new ReflectionProperty(\Illuminate\Database\Eloquent\Model::class, 'attributes');
        $prop->setValue($check, [
            'tenant_id' => $tenantId,
        ]);

        return $check;
    }
}
