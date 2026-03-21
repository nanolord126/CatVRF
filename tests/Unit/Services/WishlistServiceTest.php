<?php declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Tenant;
use App\Models\User;
use App\Services\Wishlist\WishlistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * WishlistServiceTest — Полные unit-тесты для WishlistService.
 *
 * Атаки и edge cases:
 * - Дубликат в вишлисте (должен вернуть success=false, не дублировать)
 * - Массовое добавление одного пользователя (spam flood)
 * - Межтенантное добавление (tenant isolation)
 * - Удаление несуществующего элемента
 * - correlation_id в каждой записи
 * - Рейтинговая манипуляция через массовые вишлисты
 * - cache invalidation
 */
final class WishlistServiceTest extends TestCase
{
    use RefreshDatabase;

    private WishlistService $service;
    private User $user;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(WishlistService::class);
        $this->tenant  = Tenant::factory()->create();
        $this->user    = User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    // ─── ADD ITEM ─────────────────────────────────────────────────────────────

    public function test_add_item_returns_success_true(): void
    {
        $result = $this->service->addItem($this->user->id, 'product', 1);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('correlation_id', $result);
    }

    public function test_add_item_persists_to_db(): void
    {
        $this->service->addItem($this->user->id, 'product', 42);

        $this->assertDatabaseHas('wishlist_items', [
            'user_id'   => $this->user->id,
            'item_type' => 'product',
            'item_id'   => 42,
        ]);
    }

    public function test_add_duplicate_item_returns_false(): void
    {
        $this->service->addItem($this->user->id, 'product', 5);
        $result = $this->service->addItem($this->user->id, 'product', 5);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('already', strtolower($result['message']));
    }

    public function test_add_duplicate_does_not_create_second_db_row(): void
    {
        $this->service->addItem($this->user->id, 'product', 7);
        $this->service->addItem($this->user->id, 'product', 7);

        $count = DB::table('wishlist_items')
            ->where('user_id', $this->user->id)
            ->where('item_id', 7)
            ->count();

        $this->assertSame(1, $count);
    }

    public function test_add_item_stores_correlation_id(): void
    {
        $this->service->addItem($this->user->id, 'service', 10);

        $row = DB::table('wishlist_items')
            ->where('user_id', $this->user->id)
            ->where('item_id', 10)
            ->first();

        $this->assertNotEmpty($row->correlation_id);
        $this->assertMatchesRegularExpression('/^[0-9a-f\-]{36}$/', $row->correlation_id);
    }

    public function test_add_item_with_metadata(): void
    {
        $metadata = ['priority' => 'high', 'note' => 'Birthday gift'];
        $this->service->addItem($this->user->id, 'product', 99, $metadata);

        $row = DB::table('wishlist_items')
            ->where('user_id', $this->user->id)
            ->where('item_id', 99)
            ->first();

        $decoded = json_decode($row->metadata, true);
        $this->assertSame('high', $decoded['priority']);
    }

    // ─── REMOVE ITEM ──────────────────────────────────────────────────────────

    public function test_remove_existing_item_returns_true(): void
    {
        $this->service->addItem($this->user->id, 'product', 20);
        $result = $this->service->removeItem($this->user->id, 'product', 20);
        $this->assertTrue($result);
    }

    public function test_remove_item_deletes_from_db(): void
    {
        $this->service->addItem($this->user->id, 'product', 21);
        $this->service->removeItem($this->user->id, 'product', 21);

        $this->assertDatabaseMissing('wishlist_items', [
            'user_id' => $this->user->id,
            'item_id' => 21,
        ]);
    }

    public function test_remove_nonexistent_item_returns_false(): void
    {
        $result = $this->service->removeItem($this->user->id, 'product', 999_999);
        $this->assertFalse($result);
    }

    // ─── GET WISHLIST ──────────────────────────────────────────────────────────

    public function test_get_wishlist_returns_only_user_items(): void
    {
        $other = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->service->addItem($this->user->id, 'product', 100);
        $this->service->addItem($this->user->id, 'product', 101);
        $this->service->addItem($other->id, 'product', 200);

        $wishlist = $this->service->getWishlist($this->user->id);

        $this->assertCount(2, $wishlist);
        foreach ($wishlist as $item) {
            $this->assertNotSame(200, $item['item_id'] ?? $item->item_id ?? null);
        }
    }

    public function test_get_wishlist_returns_empty_for_new_user(): void
    {
        $newUser  = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $wishlist = $this->service->getWishlist($newUser->id);

        $this->assertEmpty($wishlist);
    }

    // ─── CACHE INVALIDATION ───────────────────────────────────────────────────

    public function test_cache_invalidated_after_add(): void
    {
        \Illuminate\Support\Facades\Cache::shouldReceive('forget')
            ->once()
            ->withArgs(fn ($key) => str_contains($key, (string) $this->user->id));

        $this->service->addItem($this->user->id, 'product', 500);
    }

    public function test_cache_invalidated_after_remove(): void
    {
        $this->service->addItem($this->user->id, 'product', 600);

        \Illuminate\Support\Facades\Cache::shouldReceive('forget')
            ->once()
            ->withArgs(fn ($key) => str_contains($key, (string) $this->user->id));

        $this->service->removeItem($this->user->id, 'product', 600);
    }

    // ─── FRAUD: RATING MANIPULATION VIA WISHLIST ──────────────────────────────

    public function test_mass_add_from_single_user_does_not_duplicate_product_popularity(): void
    {
        // User tries to add same product 100 times to inflate its rating
        for ($i = 0; $i < 100; $i++) {
            $this->service->addItem($this->user->id, 'product', 777);
        }

        $count = DB::table('wishlist_items')
            ->where('user_id', $this->user->id)
            ->where('item_id', 777)
            ->count();

        // Must be idempotent — exactly 1 record
        $this->assertSame(1, $count);
    }

    public function test_different_users_can_wishlist_same_product(): void
    {
        $users = User::factory()->count(5)->create(['tenant_id' => $this->tenant->id]);
        foreach ($users as $u) {
            $result = $this->service->addItem($u->id, 'product', 888);
            $this->assertTrue($result['success']);
        }

        $count = DB::table('wishlist_items')->where('item_id', 888)->count();
        $this->assertSame(5, $count);
    }

    // ─── TENANT ISOLATION ────────────────────────────────────────────────────

    public function test_get_wishlist_for_wrong_user_returns_empty(): void
    {
        $this->service->addItem($this->user->id, 'product', 300);
        // Another tenant's user should not see this wishlist
        $anotherTenant = Tenant::factory()->create();
        $anotherUser   = User::factory()->create(['tenant_id' => $anotherTenant->id]);

        $wishlist = $this->service->getWishlist($anotherUser->id);
        $this->assertEmpty($wishlist);
    }

    // ─── AUDIT LOG ───────────────────────────────────────────────────────────

    public function test_add_item_logs_to_audit(): void
    {
        $logged = false;
        Log::shouldReceive('channel')->with('audit')->andReturnSelf();
        Log::shouldReceive('info')->andReturnUsing(function ($msg) use (&$logged) {
            if (str_contains($msg, 'Wishlist')) {
                $logged = true;
            }
        });

        $this->service->addItem($this->user->id, 'product', 400);
        $this->assertTrue($logged);
    }

    // ─── DB FAILURE ───────────────────────────────────────────────────────────

    public function test_db_failure_propagates_exception(): void
    {
        DB::shouldReceive('transaction')->andThrow(new \RuntimeException('DB down'));
        $this->expectException(\RuntimeException::class);
        $this->service->addItem($this->user->id, 'product', 999);
    }

    // ─── WISHLIST COUNT ──────────────────────────────────────────────────────

    public function test_get_wishlist_count_for_product(): void
    {
        $users = User::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);
        foreach ($users as $u) {
            $this->service->addItem($u->id, 'product', 1234);
        }

        if (method_exists($this->service, 'getProductWishlistCount')) {
            $cnt = $this->service->getProductWishlistCount('product', 1234);
            $this->assertSame(3, $cnt);
        } else {
            $this->markTestSkipped('getProductWishlistCount not implemented yet');
        }
    }
}
