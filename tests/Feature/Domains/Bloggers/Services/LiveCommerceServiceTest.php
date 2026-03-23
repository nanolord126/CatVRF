<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Bloggers\Services;

use App\Domains\Bloggers\Models\Stream;
use App\Domains\Bloggers\Models\StreamProduct;
use App\Domains\Bloggers\Models\StreamOrder;
use App\Domains\Bloggers\Models\BloggerProfile;
use App\Domains\Bloggers\Services\LiveCommerceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

final class LiveCommerceServiceTest extends TestCase
{
    use RefreshDatabase;

    private LiveCommerceService $service;
    private Stream $stream;
    private BloggerProfile $blogger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(LiveCommerceService::class);
        
        $bloggerUser = \App\Models\User::factory()->create();
        $this->blogger = BloggerProfile::factory()
            ->for($bloggerUser)
            ->create(['verification_status' => 'verified']);

        $this->stream = Stream::factory()
            ->for($this->blogger, 'blogger')
            ->create(['status' => 'live']);
    }

    public function test_add_product_to_stream_creates_stream_product(): void
    {
        $product = \App\Models\Product::factory()->create();

        $streamProduct = $this->service->addProductToStream(
            streamId: (int) $this->stream->id,
            productId: (int) $product->id,
            priceOverride: 4990,
            quantity: 50,
            correlationId: '123-456',
        );

        $this->assertInstanceOf(StreamProduct::class, $streamProduct);
        $this->assertEquals((int) $product->id, $streamProduct->product_id);
        $this->assertEquals(4990, $streamProduct->price_during_stream);
        $this->assertEquals(50, $streamProduct->quantity_available);
        $this->assertFalse($streamProduct->is_pinned);
    }

    public function test_add_product_broadcasts_event(): void
    {
        \Event::fake();
        $product = \App\Models\Product::factory()->create();

        $this->service->addProductToStream(
            streamId: (int) $this->stream->id,
            productId: (int) $product->id,
            priceOverride: 4990,
            quantity: 50,
            correlationId: '123-456',
        );

        \Event::assertDispatchedTimes(\App\Domains\Bloggers\Events\ProductAddedToStream::class);
    }

    public function test_pin_product_respects_max_limit(): void
    {
        $products = \App\Models\Product::factory(6)->create();

        foreach ($products as $product) {
            StreamProduct::create([
                'stream_id' => $this->stream->id,
                'product_id' => (int) $product->id,
                'price_during_stream' => 1000,
                'quantity_available' => 10,
                'is_pinned' => false,
            ]);
        }

        // Pin 5 products successfully
        for ($i = 0; $i < 5; $i++) {
            $this->service->pinProduct(
                streamId: (int) $this->stream->id,
                streamProductId: StreamProduct::where('stream_id', $this->stream->id)
                    ->whereNull('pin_position')
                    ->first()
                    ->id,
                correlationId: "pin-$i",
            );
        }

        // 6th pin should fail
        $sixthProduct = StreamProduct::where('stream_id', $this->stream->id)
            ->whereNull('pin_position')
            ->first();

        if ($sixthProduct) {
            $this->expectException(\Exception::class);
            $this->service->pinProduct(
                streamId: (int) $this->stream->id,
                streamProductId: $sixthProduct->id,
                correlationId: 'pin-6',
            );
        }
    }

    public function test_pin_product_sets_position(): void
    {
        $product = \App\Models\Product::factory()->create();
        $streamProduct = StreamProduct::create([
            'stream_id' => $this->stream->id,
            'product_id' => (int) $product->id,
            'price_during_stream' => 1000,
            'quantity_available' => 10,
            'is_pinned' => false,
        ]);

        $pinned = $this->service->pinProduct(
            streamId: (int) $this->stream->id,
            streamProductId: $streamProduct->id,
            correlationId: 'pin-1',
        );

        $this->assertTrue($pinned->is_pinned);
        $this->assertNotNull($pinned->pin_position);
        $this->assertEquals(1, $pinned->pin_position);
    }

    public function test_unpin_product_clears_pinned_status(): void
    {
        $product = \App\Models\Product::factory()->create();
        $streamProduct = StreamProduct::create([
            'stream_id' => $this->stream->id,
            'product_id' => (int) $product->id,
            'price_during_stream' => 1000,
            'quantity_available' => 10,
            'is_pinned' => true,
            'pin_position' => 1,
            'pinned_at' => now(),
        ]);

        $unpinned = $this->service->unpinProduct(
            streamId: (int) $this->stream->id,
            streamProductId: $streamProduct->id,
            correlationId: 'unpin-1',
        );

        $this->assertFalse($unpinned->is_pinned);
        $this->assertNull($unpinned->pin_position);
        $this->assertNull($unpinned->pinned_at);
    }

    public function test_create_and_pay_order_creates_order(): void
    {
        $product = \App\Models\Product::factory()->create();
        $streamProduct = StreamProduct::create([
            'stream_id' => $this->stream->id,
            'product_id' => (int) $product->id,
            'price_during_stream' => 4990,
            'quantity_available' => 50,
        ]);

        $buyer = \App\Models\User::factory()->create();

        $order = $this->service->createAndPayOrder(
            streamId: (int) $this->stream->id,
            userId: $buyer->id,
            productId: $streamProduct->id,
            quantity: 2,
            paymentMethod: 'sbp',
            correlationId: 'order-1',
        );

        $this->assertInstanceOf(StreamOrder::class, $order);
        $this->assertEquals('pending', $order->status);
        $this->assertEquals($buyer->id, $order->user_id);
        $this->assertEquals(9980, $order->subtotal);
    }

    public function test_create_order_calculates_correct_total(): void
    {
        $product = \App\Models\Product::factory()->create();
        $streamProduct = StreamProduct::create([
            'stream_id' => $this->stream->id,
            'product_id' => (int) $product->id,
            'price_during_stream' => 5000,
            'quantity_available' => 50,
        ]);

        $buyer = \App\Models\User::factory()->create();

        $order = $this->service->createAndPayOrder(
            streamId: (int) $this->stream->id,
            userId: $buyer->id,
            productId: $streamProduct->id,
            quantity: 2,
            paymentMethod: 'card',
            correlationId: 'order-2',
        );

        // Subtotal: 5000 * 2 = 10000
        $this->assertEquals(10000, $order->subtotal);
        // Total = subtotal + shipping - discount (no discount here)
        $this->assertEquals(10000, $order->total);
    }

    public function test_confirm_payment_marks_order_as_paid(): void
    {
        $product = \App\Models\Product::factory()->create();
        $streamProduct = StreamProduct::create([
            'stream_id' => $this->stream->id,
            'product_id' => (int) $product->id,
            'price_during_stream' => 5000,
            'quantity_available' => 50,
        ]);

        $buyer = \App\Models\User::factory()->create();

        $order = StreamOrder::create([
            'stream_id' => $this->stream->id,
            'user_id' => $buyer->id,
            'stream_product_id' => $streamProduct->id,
            'quantity' => 1,
            'subtotal' => 5000,
            'total' => 5000,
            'payment_method' => 'sbp',
            'status' => 'pending',
            'idempotency_key' => 'test-' . time(),
        ]);

        $paid = $this->service->confirmPayment(
            orderId: $order->id,
            correlationId: 'confirm-1',
        );

        $this->assertEquals('paid', $paid->status);
        $this->assertNotNull($paid->paid_at);
    }

    public function test_confirm_payment_increments_product_sold_quantity(): void
    {
        $product = \App\Models\Product::factory()->create();
        $streamProduct = StreamProduct::create([
            'stream_id' => $this->stream->id,
            'product_id' => (int) $product->id,
            'price_during_stream' => 5000,
            'quantity_available' => 50,
            'quantity_sold' => 0,
        ]);

        $buyer = \App\Models\User::factory()->create();

        $order = StreamOrder::create([
            'stream_id' => $this->stream->id,
            'user_id' => $buyer->id,
            'stream_product_id' => $streamProduct->id,
            'quantity' => 3,
            'subtotal' => 15000,
            'total' => 15000,
            'payment_method' => 'sbp',
            'status' => 'pending',
            'idempotency_key' => 'test-' . time(),
        ]);

        $this->service->confirmPayment($order->id, 'confirm-2');

        $streamProduct->refresh();
        $this->assertEquals(3, $streamProduct->quantity_sold);
    }

    public function test_confirm_payment_applies_14_percent_commission(): void
    {
        $product = \App\Models\Product::factory()->create();
        $streamProduct = StreamProduct::create([
            'stream_id' => $this->stream->id,
            'product_id' => (int) $product->id,
            'price_during_stream' => 10000,
            'quantity_available' => 50,
        ]);

        $buyer = \App\Models\User::factory()->create();

        $order = StreamOrder::create([
            'stream_id' => $this->stream->id,
            'user_id' => $buyer->id,
            'stream_product_id' => $streamProduct->id,
            'quantity' => 1,
            'subtotal' => 10000,
            'total' => 10000,
            'payment_method' => 'sbp',
            'status' => 'pending',
            'idempotency_key' => 'test-' . time(),
        ]);

        $this->service->confirmPayment($order->id, 'confirm-3');

        $this->stream->refresh();
        // 14% of 10000 = 1400
        $this->assertEquals(1400, $this->stream->platform_commission);
    }

    public function test_create_order_logs_to_audit(): void
    {
        Log::shouldReceive('channel')
            ->with('audit')
            ->andReturnSelf()
            ->shouldReceive('info');

        $product = \App\Models\Product::factory()->create();
        $streamProduct = StreamProduct::create([
            'stream_id' => $this->stream->id,
            'product_id' => (int) $product->id,
            'price_during_stream' => 5000,
            'quantity_available' => 50,
        ]);

        $buyer = \App\Models\User::factory()->create();

        $this->service->createAndPayOrder(
            streamId: (int) $this->stream->id,
            userId: $buyer->id,
            productId: $streamProduct->id,
            quantity: 1,
            paymentMethod: 'sbp',
            correlationId: 'order-3',
        );
    }
}
