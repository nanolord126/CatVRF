<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Bloggers\Http;

use App\Domains\Bloggers\Models\Stream;
use App\Domains\Bloggers\Models\BloggerProfile;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class ProductControllerApiTest extends TestCase
{
    use RefreshDatabase;

    protected BloggerProfile $blogger;
    protected Stream $stream;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->blogger = BloggerProfile::factory()->verified()->create();
        $this->stream = Stream::factory()
            ->for($this->blogger, 'blogger')
            ->create(['status' => 'live']);
        $this->product = Product::factory()->create();
        $this->actingAs($this->blogger->user);
    }

    /**
     * POST /api/streams/{roomId}/products
     * Test: Add product to stream
     */
    public function test_add_product_endpoint(): void
    {
        $response = $this->postJson("/api/streams/{$this->stream->room_id}/products", [
            'product_id' => $this->product->id,
            'quantity' => 100,
            'price_override' => 4990,
        ]);

        $this->assertEquals(201, $response->status());
        $this->assertNotNull($response->json('data.id'));
    }

    /**
     * GET /api/streams/{roomId}/products
     * Test: Get stream products
     */
    public function test_get_stream_products_endpoint(): void
    {
        $this->postJson("/api/streams/{$this->stream->room_id}/products", [
            'product_id' => $this->product->id,
            'quantity' => 50,
        ]);

        $response = $this->getJson("/api/streams/{$this->stream->room_id}/products");

        $this->assertEquals(200, $response->status());
        $this->assertGreaterThan(0, count($response->json('data')));
    }

    /**
     * POST /api/streams/{roomId}/products/{productId}/pin
     * Test: Pin product endpoint
     */
    public function test_pin_product_endpoint(): void
    {
        $streamProduct = $this->stream->products()->create([
            'product_id' => $this->product->id,
            'quantity' => 50,
            'price' => 4990,
        ]);

        $response = $this->postJson(
            "/api/streams/{$this->stream->room_id}/products/{$streamProduct->id}/pin"
        );

        $this->assertEquals(200, $response->status());
        $streamProduct->refresh();
        $this->assertNotNull($streamProduct->pin_position);
    }

    /**
     * POST /api/streams/{roomId}/products/{productId}/unpin
     * Test: Unpin product endpoint
     */
    public function test_unpin_product_endpoint(): void
    {
        $streamProduct = $this->stream->products()->create([
            'product_id' => $this->product->id,
            'quantity' => 50,
            'price' => 4990,
            'pin_position' => 1,
        ]);

        $response = $this->postJson(
            "/api/streams/{$this->stream->room_id}/products/{$streamProduct->id}/unpin"
        );

        $this->assertEquals(200, $response->status());
        $streamProduct->refresh();
        $this->assertNull($streamProduct->pin_position);
    }

    /**
     * DELETE /api/streams/{roomId}/products/{productId}
     * Test: Remove product from stream
     */
    public function test_remove_product_endpoint(): void
    {
        $streamProduct = $this->stream->products()->create([
            'product_id' => $this->product->id,
            'quantity' => 50,
            'price' => 4990,
        ]);

        $response = $this->deleteJson(
            "/api/streams/{$this->stream->room_id}/products/{$streamProduct->id}"
        );

        $this->assertEquals(200, $response->status());
        $this->assertNull($this->stream->products()->find($streamProduct->id));
    }
}
