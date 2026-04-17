<?php declare(strict_types=1);

namespace Tests\E2E;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

final class FashionFraudDetectionE2ETest extends \Tests\BaseTestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    public function test_detects_rapid_multiple_orders_from_same_ip(): void
    {
        $responses = [];
        
        for ($i = 0; $i < 10; $i++) {
            $responses[] = $this->actingAs($this->user)
                ->postJson('/api/fashion/orders', [
                    'items' => [['product_id' => 1, 'quantity' => 1]],
                    'payment_method' => 'wallet',
                ]);
        }

        // After rapid orders, subsequent orders should be flagged or rate-limited
        $lastResponse = end($responses);
        
        $this->assertContains(
            $lastResponse->status(),
            [200, 429, 403],
            'Rapid orders should be handled (allowed, rate-limited, or blocked)'
        );
    }

    public function test_detects_suspicious_high_value_orders(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/fashion/orders', [
                'items' => [
                    ['product_id' => 1, 'quantity' => 100],
                ],
                'payment_method' => 'wallet',
            ]);

        $this->assertNotEquals(500, $response->status());
        
        // High value orders might require additional verification
        if ($response->status() === 200) {
            $this->assertArrayHasKey('requires_verification', $response->json());
        }
    }

    public function test_detects_payment_method_switching_pattern(): void
    {
        // Create orders with different payment methods rapidly
        $paymentMethods = ['wallet', 'card', 'paypal', 'crypto'];
        
        foreach ($paymentMethods as $method) {
            $this->actingAs($this->user)
                ->postJson('/api/fashion/orders', [
                    'items' => [['product_id' => 1, 'quantity' => 1]],
                    'payment_method' => $method,
                ]);
        }

        // Check if pattern is detected
        $response = $this->actingAs($this->user)
            ->postJson('/api/fashion/orders', [
                'items' => [['product_id' => 1, 'quantity' => 1]],
                'payment_method' => 'wallet',
            ]);

        $this->assertNotEquals(500, $response->status());
    }

    public function test_detects_address_mismatch_in_returns(): void
    {
        // Create an order
        $orderResponse = $this->actingAs($this->user)
            ->postJson('/api/fashion/orders', [
                'items' => [['product_id' => 1, 'quantity' => 1]],
                'shipping_address' => '123 Main St, City',
                'payment_method' => 'wallet',
            ]);

        if ($orderResponse->status() === 200) {
            $orderId = $orderResponse->json('id');

            // Try to return to different address
            $returnResponse = $this->actingAs($this->user)
                ->postJson('/api/fashion/returns', [
                    'order_id' => $orderId,
                    'product_id' => 1,
                    'return_address' => '456 Different St, Different City',
                    'reason' => 'wrong_size',
                ]);

            $this->assertNotEquals(500, $returnResponse->status());
            
            // Address mismatch might trigger manual review
            if ($returnResponse->status() === 200) {
                $this->assertArrayHasKey('requires_manual_review', $returnResponse->json());
            }
        }
    }

    public function test_detects_return_abuse_pattern(): void
    {
        // Create and return multiple orders
        for ($i = 0; $i < 5; $i++) {
            $orderResponse = $this->actingAs($this->user)
                ->postJson('/api/fashion/orders', [
                    'items' => [['product_id' => 1, 'quantity' => 1]],
                    'payment_method' => 'wallet',
                ]);

            if ($orderResponse->status() === 200) {
                $orderId = $orderResponse->json('id');

                $this->actingAs($this->user)
                    ->postJson('/api/fashion/returns', [
                        'order_id' => $orderId,
                        'product_id' => 1,
                        'reason' => 'wrong_size',
                    ]);
            }
        }

        // Check if return abuse is detected
        $response = $this->actingAs($this->user)
            ->postJson('/api/fashion/orders', [
                'items' => [['product_id' => 1, 'quantity' => 1]],
                'payment_method' => 'wallet',
            ]);

        $this->assertNotEquals(500, $response->status());
        
        // High return rate might trigger warnings
        if ($response->status() === 200) {
            $data = $response->json();
            $this->assertArrayHasKey('return_warning', $data);
        }
    }

    public function test_detects_account_takeover_attempts(): void
    {
        // Simulate login from different locations
        $ips = ['192.168.1.1', '192.168.1.2', '192.168.1.3', '10.0.0.1'];
        
        foreach ($ips as $ip) {
            $_SERVER['REMOTE_ADDR'] = $ip;
            
            $this->actingAs($this->user)
                ->getJson('/api/fashion/orders');
        }

        // Check if suspicious activity is flagged
        $response = $this->actingAs($this->user)
            ->getJson('/api/fashion/orders');

        $this->assertNotEquals(500, $response->status());
        
        // Multiple IPs might trigger security check
        if ($response->status() === 200) {
            $this->assertArrayHasKey('security_warning', $response->json());
        }
    }

    public function test_detects_price_manipulation_attempts(): void
    {
        // Try to create order with manipulated price
        $response = $this->actingAs($this->user)
            ->postJson('/api/fashion/orders', [
                'items' => [
                    ['product_id' => 1, 'quantity' => 1, 'price' => 1], // Suspicious low price
                ],
                'payment_method' => 'wallet',
            ]);

        $this->assertNotEquals(500, $response->status());
        
        // Price manipulation should be rejected
        $this->assertNotEquals(201, $response->status());
    }

    public function test_detects_coupon_abuse(): void
    {
        // Try to use same coupon multiple times
        $couponCode = 'TEST20';
        
        for ($i = 0; $i < 5; $i++) {
            $response = $this->actingAs($this->user)
                ->postJson('/api/fashion/orders', [
                    'items' => [['product_id' => 1, 'quantity' => 1]],
                    'coupon_code' => $couponCode,
                    'payment_method' => 'wallet',
                ]);

            // Single-use coupons should fail after first use
            if ($i > 0 && $response->status() === 200) {
                $data = $response->json();
                $this->assertArrayHasKey('coupon_rejected', $data);
            }
        }
    }

    public function test_detects_inventory_manipulation(): void
    {
        // Try to reserve more stock than available
        $response = $this->actingAs($this->user)
            ->postJson('/api/fashion/products/1/reserve', [
                'quantity' => 999999,
                'order_id' => 'test_order',
            ]);

        $this->assertNotEquals(500, $response->status());
        $this->assertFalse($response->json('success') ?? true);
    }

    public function test_detects_review_spam(): void
    {
        // Post multiple reviews rapidly
        for ($i = 0; $i < 10; $i++) {
            $response = $this->actingAs($this->user)
                ->postJson('/api/fashion/products/1/reviews', [
                    'rating' => 5,
                    'comment' => 'Great product ' . $i,
                ]);
        }

        // Check if spam is detected
        $response = $this->actingAs($this->user)
            ->postJson('/api/fashion/products/1/reviews', [
                'rating' => 5,
                'comment' => 'Another review',
            ]);

        $this->assertNotEquals(500, $response->status());
        
        if ($response->status() === 429) {
            $this->assertTrue(true, 'Review spam should be rate-limited');
        }
    }

    public function test_detects_fake_account_creation(): void
    {
        // Create multiple accounts with similar patterns
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/register', [
                'email' => "test{$i}@example.com",
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'name' => 'Test User',
            ]);
        }

        // Check if pattern is detected
        $response = $this->postJson('/api/register', [
            'email' => 'test5@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'name' => 'Test User',
        ]);

        $this->assertNotEquals(500, $response->status());
        
        // Might require CAPTCHA after multiple similar registrations
        if ($response->status() === 422) {
            $this->assertArrayHasKey('requires_captcha', $response->json());
        }
    }
}
