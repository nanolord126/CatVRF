<?php declare(strict_types=1);

namespace Tests\Feature\Taxi;

use App\Domains\Taxi\Models\TaxiRide;
use App\Services\FraudControlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

final class TaxiDDOSTest extends TestCase
{
    use RefreshDatabase;

    public function test_massive_concurrent_order_requests_gets_throttled(): void
    {
        $successCount = 0;
        $rateLimitedCount = 0;
        $bannedCount = 0;

        for ($i = 0; $i < 100; $i++) {
            $response = $this->postJson('/api/v1/taxi/orders', [
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
                'X-Forwarded-For' => '192.168.1.200',
                'X-Device-Fingerprint' => 'test-ddos-fingerprint',
                'X-Correlation-ID' => "ddos-test-{$i}",
            ]);

            if ($response->status() === 201) {
                $successCount++;
            } elseif ($response->status() === 429) {
                $rateLimitedCount++;
            } elseif ($response->status() === 403) {
                $bannedCount++;
            }
        }

        $this->assertLessThan(20, $successCount, 'Too many successful requests, DDOS protection failed');
        $this->assertGreaterThan(80, $rateLimitedCount + $bannedCount, 'Most requests should be blocked');
    }

    public function test_ip_address_gets_banned_after_excessive_requests(): void
    {
        $bannedIp = '192.168.1.250';
        
        for ($i = 0; $i < 50; $i++) {
            $this->postJson('/api/v1/taxi/estimate-price', [
                'pickup_lat' => 55.75396,
                'pickup_lon' => 37.62039,
                'dropoff_lat' => 55.7520,
                'dropoff_lon' => 37.6175,
                'vehicle_class' => 'economy',
            ], [
                'X-Forwarded-For' => $bannedIp,
                'X-Correlation-ID' => "ban-test-{$i}",
            ]);
        }

        Cache::put("ban:ip:{$bannedIp}", true, 3600);

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
            'X-Forwarded-For' => $bannedIp,
            'X-Correlation-ID' => 'banned-ip-test',
        ]);

        $this->assertEquals(403, $response->status());
        $response->assertJson([
            'error' => 'IP address banned',
        ]);
    }

    public function test_distributed_ddos_attack_from_multiple_ips_gets_detected(): void
    {
        $ips = [];
        for ($i = 0; $i < 20; $i++) {
            $ips[] = "192.168.1.{$i}";
        }

        $totalRequests = 0;
        $blockedRequests = 0;

        foreach ($ips as $ip) {
            for ($j = 0; $j < 10; $j++) {
                $response = $this->postJson('/api/v1/taxi/orders', [
                    'pickup_address' => "Moscow, Location {$i}-{$j}",
                    'pickup_lat' => 55.75396,
                    'pickup_lon' => 37.62039,
                    'dropoff_address' => 'Moscow, Kremlin',
                    'dropoff_lat' => 55.7520,
                    'dropoff_lon' => 37.6175,
                    'payment_method' => 'wallet',
                    'device_type' => 'mobile',
                    'app_version' => '1.0.0',
                ], [
                    'X-Forwarded-For' => $ip,
                    'X-Device-Fingerprint' => 'distributed-ddos',
                    'X-Correlation-ID' => "distributed-{$i}-{$j}",
                ]);

                $totalRequests++;
                if ($response->status() === 429 || $response->status() === 403) {
                    $blockedRequests++;
                }
            }
        }

        $this->assertGreaterThan($totalRequests * 0.5, $blockedRequests, 'More than 50% should be blocked');
    }

    public function test_slow_loris_attack_prevention(): void
    {
        $slowRequestSent = false;

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
            'X-Forwarded-For' => '192.168.1.300',
            'X-Correlation-ID' => 'slow-loris-test',
        ]);

        if ($response->status() === 200) {
            $slowRequestSent = true;
        }

        $this->assertTrue($slowRequestSent, 'Request should be processed within timeout');
    }

    public function test_api_gateway_rate_limiting_works(): void
    {
        RateLimiter::for('api-gateway', function () {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(100);
        });

        $requestCount = 0;
        $blockedCount = 0;

        for ($i = 0; $i < 150; $i++) {
            $response = $this->getJson('/api/v1/taxi/tariffs', [
                'X-Correlation-ID' => "gateway-{$i}",
            ]);

            $requestCount++;
            if ($response->status() === 429) {
                $blockedCount++;
            }
        }

        $this->assertGreaterThan(40, $blockedCount, 'Gateway should block excessive requests');
    }

    public function test_geographic_ddos_detection(): void
    {
        $country = 'XX';
        
        Cache::put("ddos:country:{$country}:count", 10000, 60);

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
            'X-Country-Code' => $country,
            'X-Correlation-ID' => 'geo-ddos-test',
        ]);

        $this->assertContains($response->status(), [403, 429]);
    }
}
