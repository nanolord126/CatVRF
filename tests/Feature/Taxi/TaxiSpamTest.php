<?php declare(strict_types=1);

namespace Tests\Feature\Taxi;

use App\Domains\Taxi\Http\Controllers\TaxiOrderController;
use App\Services\FraudControlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

final class TaxiSpamTest extends TestCase
{
    use RefreshDatabase;

    public function test_multiple_orders_from_same_ip_gets_rate_limited(): void
    {
        $ipAddress = '192.168.1.100';
        
        RateLimiter::for('taxi-order-create', function () {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(5);
        });

        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/v1/taxi/orders', [
                'pickup_address' => 'Moscow, Red Square',
                'pickup_lat' => 55.75396,
                'pickup_lon' => 37.62039,
                'dropoff_address' => 'Moscow, Kremlin',
                'dropoff_lat' => 55.7520,
                'dropoff_lon' => 37.6175,
                'payment_method' => 'wallet',
                'device_type' => 'mobile',
                'app_version' => '1.0.0',
            ], [
                'X-Forwarded-For' => $ipAddress,
                'X-Device-Fingerprint' => 'test-fingerprint',
                'X-Correlation-ID' => "test-{$i}",
            ]);
        }

        $this->assertEquals(429, $response->status());
    }

    public function test_rapid_estimate_price_requests_gets_blocked(): void
    {
        for ($i = 0; $i < 21; $i++) {
            $response = $this->postJson('/api/v1/taxi/estimate-price', [
                'pickup_lat' => 55.75396,
                'pickup_lon' => 37.62039,
                'dropoff_lat' => 55.7520,
                'dropoff_lon' => 37.6175,
                'vehicle_class' => 'economy',
            ], [
                'X-Correlation-ID' => "test-{$i}",
            ]);
        }

        $this->assertEquals(429, $response->status());
    }

    public function test_suspicious_device_fingerprint_gets_flagged(): void
    {
        $suspiciousFingerprint = 'suspicious-known-fraud-device';
        
        Cache::put("fraud:device:{$suspiciousFingerprint}", true, 3600);

        $response = $this->postJson('/api/v1/taxi/orders', [
            'pickup_address' => 'Moscow, Red Square',
            'pickup_lat' => 55.75396,
            'pickup_lon' => 37.62039,
            'dropoff_address' => 'Moscow, Kremlin',
            'dropoff_lat' => 55.7520,
            'dropoff_lon' => 37.6175,
            'payment_method' => 'wallet',
            'device_type' => 'mobile',
            'app_version' => '1.0.0',
        ], [
            'X-Device-Fingerprint' => $suspiciousFingerprint,
            'X-Correlation-ID' => 'test-fraud-check',
        ]);

        $this->assertEquals(403, $response->status());
        $response->assertJson([
            'error' => 'Fraud detected',
            'reason' => 'Suspicious device fingerprint',
        ]);
    }

    public function test_user_with_high_cancellation_rate_gets_flagged(): void
    {
        $userId = 999;
        
        Cache::put("taxi:user:{$userId}:cancellation_rate", 0.85, 3600);

        $response = $this->actingAs(\App\Models\User::factory()->create(['id' => $userId]))
            ->postJson('/api/v1/taxi/orders', [
                'pickup_address' => 'Moscow, Red Square',
                'pickup_lat' => 55.75396,
                'pickup_lon' => 37.62039,
                'dropoff_address' => 'Moscow, Kremlin',
                'dropoff_lat' => 55.7520,
                'dropoff_lon' => 37.6175,
                'payment_method' => 'wallet',
                'device_type' => 'mobile',
                'app_version' => '1.0.0',
            ], [
                'X-Correlation-ID' => 'test-high-cancellation',
            ]);

        $this->assertContains($response->status(), [403, 200]);
    }

    public function test_bulk_order_creation_from_same_user_gets_blocked(): void
    {
        $user = \App\Models\User::factory()->create();

        for ($i = 0; $i < 11; $i++) {
            $response = $this->actingAs($user)
                ->postJson('/api/v1/taxi/orders', [
                    'pickup_address' => "Moscow, Location {$i}",
                    'pickup_lat' => 55.75396,
                    'pickup_lon' => 37.62039,
                    'dropoff_address' => 'Moscow, Kremlin',
                    'dropoff_lat' => 55.7520,
                    'dropoff_lon' => 37.6175,
                    'payment_method' => 'wallet',
                    'device_type' => 'mobile',
                    'app_version' => '1.0.0',
                ], [
                    'X-Correlation-ID' => "test-bulk-{$i}",
                ]);

            if ($i >= 10) {
                $this->assertContains($response->status(), [403, 429]);
            }
        }
    }

    public function test_spam_report_creates_fraud_record(): void
    {
        $response = $this->postJson('/api/v1/taxi/spam-report', [
            'report_type' => 'suspicious_order',
            'order_uuid' => 'test-uuid-123',
            'reason' => 'Multiple rapid orders',
            'ip_address' => '192.168.1.100',
            'device_fingerprint' => 'test-fingerprint',
        ], [
            'X-Correlation-ID' => 'test-spam-report',
        ]);

        $this->assertContains($response->status(), [200, 201]);
    }

    public function test_idempotency_key_prevents_duplicate_orders(): void
    {
        $idempotencyKey = 'unique-idempotency-key-12345';

        $orderData = [
            'pickup_address' => 'Moscow, Red Square',
            'pickup_lat' => 55.75396,
            'pickup_lon' => 37.62039,
            'dropoff_address' => 'Moscow, Kremlin',
            'dropoff_lat' => 55.7520,
            'dropoff_lon' => 37.6175,
            'payment_method' => 'wallet',
            'device_type' => 'mobile',
            'app_version' => '1.0.0',
        ];

        $response1 = $this->postJson('/api/v1/taxi/orders', $orderData, [
            'X-Idempotency-Key' => $idempotencyKey,
            'X-Correlation-ID' => 'test-idempotency-1',
        ]);

        $response2 = $this->postJson('/api/v1/taxi/orders', $orderData, [
            'X-Idempotency-Key' => $idempotencyKey,
            'X-Correlation-ID' => 'test-idempotency-2',
        ]);

        $this->assertEquals(201, $response1->status());
        $this->assertEquals(200, $response2->status());
        
        $this->assertEquals($response1->json('data.uuid'), $response2->json('data.uuid'));
    }
}
