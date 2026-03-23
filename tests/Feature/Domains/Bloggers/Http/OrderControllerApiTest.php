<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Bloggers\Http;

use App\Domains\Bloggers\Models\Stream;
use App\Domains\Bloggers\Models\BloggerProfile;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class OrderControllerApiTest extends TestCase
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
        $this->actingAs(\App\Models\User::factory()->create());
    }

    /**
     * POST /api/orders
     * Test: Create order endpoint
     */
    public function test_create_order_endpoint(): void
    {
        $streamProduct = $this->stream->products()->create([
            'product_id' => $this->product->id,
            'quantity' => 100,
            'price' => 4990,
        ]);

        $response = $this->postJson('/api/orders', [
            'product_id' => $streamProduct->id,
            'quantity' => 2,
            'payment_method' => 'sbp',
        ]);

        $this->assertEquals(201, $response->status());
        $this->assertNotNull($response->json('data.payment_id'));
        $this->assertEquals('pending', $response->json('data.payment_status'));
    }

    /**
     * GET /api/orders/{orderId}
     * Test: Get order details
     */
    public function test_get_order_endpoint(): void
    {
        $streamProduct = $this->stream->products()->create([
            'product_id' => $this->product->id,
            'quantity' => 100,
            'price' => 4990,
        ]);

        $response = $this->postJson('/api/orders', [
            'product_id' => $streamProduct->id,
            'quantity' => 1,
            'payment_method' => 'card',
        ]);

        $orderId = $response->json('data.id');
        $response = $this->getJson("/api/orders/{$orderId}");

        $this->assertEquals(200, $response->status());
        $this->assertEquals($orderId, $response->json('data.id'));
    }

    /**
     * POST /api/orders/{orderId}/confirm-payment
     * Test: Confirm payment endpoint
     */
    public function test_confirm_payment_endpoint(): void
    {
        $streamProduct = $this->stream->products()->create([
            'product_id' => $this->product->id,
            'quantity' => 100,
            'price' => 10000,
        ]);

        $createResponse = $this->postJson('/api/orders', [
            'product_id' => $streamProduct->id,
            'quantity' => 1,
            'payment_method' => 'sbp',
        ]);

        $orderId = $createResponse->json('data.id');
        $paymentId = $createResponse->json('data.payment_id');

        $response = $this->postJson("/api/orders/{$orderId}/confirm-payment", [
            'payment_id' => $paymentId,
        ]);

        $this->assertEquals(200, $response->status());
        $this->assertEquals('confirmed', $response->json('data.payment_status'));
    }

    /**
     * GET /api/streams/{roomId}/orders
     * Test: Get stream orders
     */
    public function test_get_stream_orders_endpoint(): void
    {
        $streamProduct = $this->stream->products()->create([
            'product_id' => $this->product->id,
            'quantity' => 100,
            'price' => 4990,
        ]);

        $this->postJson('/api/orders', [
            'product_id' => $streamProduct->id,
            'quantity' => 1,
            'payment_method' => 'card',
        ]);

        $this->actingAs($this->blogger->user);
        $response = $this->getJson("/api/streams/{$this->stream->room_id}/orders");

        $this->assertEquals(200, $response->status());
        $this->assertGreaterThan(0, count($response->json('data')));
    }

    /**
     * POST /api/orders/{orderId}/refund
     * Test: Refund order endpoint
     */
    public function test_refund_order_endpoint(): void
    {
        $streamProduct = $this->stream->products()->create([
            'product_id' => $this->product->id,
            'quantity' => 100,
            'price' => 5000,
        ]);

        $createResponse = $this->postJson('/api/orders', [
            'product_id' => $streamProduct->id,
            'quantity' => 1,
            'payment_method' => 'card',
        ]);

        $orderId = $createResponse->json('data.id');
        $paymentId = $createResponse->json('data.payment_id');

        // Confirm first
        $this->postJson("/api/orders/{$orderId}/confirm-payment", [
            'payment_id' => $paymentId,
        ]);

        // Then refund
        $response = $this->postJson("/api/orders/{$orderId}/refund");

        $this->assertEquals(200, $response->status());
        $this->assertEquals('refunded', $response->json('data.refund_status'));
    }
}
