<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Wallet\Resources;

use App\Domains\Wallet\Resources\WalletResource;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

final class WalletResourceTest extends TestCase
{
    public function test_to_array_returns_expected_keys(): void
    {
        $walletStub = new \stdClass();
        $walletStub->id = 1;
        $walletStub->uuid = 'test-uuid';
        $walletStub->tenant_id = 10;
        $walletStub->business_group_id = 5;
        $walletStub->current_balance = 50000;
        $walletStub->hold_amount = 10000;
        $walletStub->is_active = true;
        $walletStub->correlation_id = 'corr-res-1';
        $walletStub->tags = ['test'];
        $walletStub->metadata = null;
        $walletStub->created_at = null;
        $walletStub->updated_at = null;

        $resource = new WalletResource($walletStub);
        $request = Request::create('/test');

        $array = $resource->toArray($request);

        $this->assertSame(1, $array['id']);
        $this->assertSame('test-uuid', $array['uuid']);
        $this->assertSame(10, $array['tenant_id']);
        $this->assertSame(5, $array['business_group_id']);
        $this->assertSame(50000, $array['current_balance']);
        $this->assertSame(10000, $array['hold_amount']);
        $this->assertSame(40000, $array['available_balance']);
        $this->assertTrue($array['is_active']);
        $this->assertSame('corr-res-1', $array['correlation_id']);
        $this->assertSame(['test'], $array['tags']);
        $this->assertNull($array['metadata']);
    }

    public function test_available_balance_is_computed_correctly(): void
    {
        $walletStub = new \stdClass();
        $walletStub->id = 2;
        $walletStub->uuid = 'uuid-2';
        $walletStub->tenant_id = 10;
        $walletStub->business_group_id = null;
        $walletStub->current_balance = 100000;
        $walletStub->hold_amount = 100000;
        $walletStub->is_active = true;
        $walletStub->correlation_id = 'corr-res-2';
        $walletStub->tags = null;
        $walletStub->metadata = null;
        $walletStub->created_at = null;
        $walletStub->updated_at = null;

        $resource = new WalletResource($walletStub);
        $array = $resource->toArray(Request::create('/test'));

        $this->assertSame(0, $array['available_balance']);
    }

    public function test_with_returns_meta_with_correlation_id(): void
    {
        $walletStub = new \stdClass();

        $resource = new WalletResource($walletStub);
        $request = Request::create('/test', 'GET', [], [], [], ['HTTP_X-Correlation-ID' => 'corr-meta']);

        $meta = $resource->with($request);

        $this->assertArrayHasKey('meta', $meta);
        $this->assertSame('corr-meta', $meta['meta']['correlation_id']);
    }
}
