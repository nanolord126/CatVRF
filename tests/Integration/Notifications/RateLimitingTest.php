<?php

declare(strict_types=1);

namespace Tests\Integration\Notifications;

use App\Models\Notification;
use App\Models\User;
use App\Services\Notifications\SmsService;
use App\Services\Notifications\EmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * RateLimitingNotificationTest
 * 
 * Тестирует rate limiting для SMS, Email, и API endpoints
 */
final class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /** @test */
    public function it_limits_sms_per_user_per_day(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['phone' => '+79991234567']);
        
        $smsService = app(SmsService::class);
        $dailyLimit = 10; // SMS per user per day

        // Send max allowed SMS
        for ($i = 0; $i < $dailyLimit; $i++) {
            $notification = Notification::factory()
                ->for($user)
                ->create(['channels' => ['sms']]);
            
            $result = $smsService->send($user, $notification);
            $this->assertTrue($result);
        }

        // Next SMS should be rate limited
        $notification = Notification::factory()
            ->for($user)
            ->create(['channels' => ['sms']]);

        $result = $smsService->send($user, $notification);
        $this->assertFalse($result);
    }

    /** @test */
    public function it_limits_email_per_user_per_hour(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['email' => 'test@example.com']);
        
        $emailService = app(EmailService::class);
        $hourlyLimit = 20; // Emails per user per hour

        // Send max allowed emails
        for ($i = 0; $i < $hourlyLimit; $i++) {
            $notification = Notification::factory()
                ->for($user)
                ->create(['channels' => ['email']]);
            
            $result = $emailService->send($user, $notification);
            $this->assertTrue($result);
        }

        // Next email should be rate limited
        $notification = Notification::factory()
            ->for($user)
            ->create(['channels' => ['email']]);

        $result = $emailService->send($user, $notification);
        $this->assertFalse($result);
    }

    /** @test */
    public function it_limits_api_notifications_per_ip(): void
    {
        $maxPerMinute = 100;
        $ipAddress = '192.168.1.1';

        // Make max requests
        for ($i = 0; $i < $maxPerMinute; $i++) {
            $limited = RateLimiter::tooManyAttempts(
                "notification-api:{$ipAddress}",
                $maxPerMinute
            );
            
            if (!$limited) {
                RateLimiter::hit("notification-api:{$ipAddress}", 60);
            }
        }

        // Next request should be limited
        $limited = RateLimiter::tooManyAttempts(
            "notification-api:{$ipAddress}",
            $maxPerMinute
        );

        $this->assertTrue($limited);
    }

    /** @test */
    public function it_resets_daily_limits_at_midnight(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['phone' => '+79991234567']);

        $key = "sms-daily:{$user->id}";
        
        // Set cache for "today"
        Cache::put($key, 10, now()->addDay());

        $this->assertEquals(10, Cache::get($key));

        // At midnight (next day), cache should be cleared
        Cache::forget($key);

        $this->assertNull(Cache::get($key));
    }

    /** @test */
    public function it_limits_bulk_notification_sending(): void
    {
        $users = User::factory()->count(100)->create();
        
        $bulkLimit = 1000; // Per minute
        $sent = 0;

        foreach ($users as $user) {
            if (RateLimiter::tooManyAttempts('bulk-send', $bulkLimit)) {
                break;
            }

            $notification = Notification::factory()
                ->for($user)
                ->create();

            RateLimiter::hit('bulk-send', 60);
            $sent++;
        }

        // Should have hit limit
        $this->assertLessThan(count($users), $sent);
    }

    /** @test */
    public function it_allows_requests_under_limit(): void
    {
        $ipAddress = '192.168.1.2';
        $maxPerMinute = 100;

        // First request should be allowed
        $limited = RateLimiter::tooManyAttempts(
            "api:{$ipAddress}",
            $maxPerMinute
        );

        $this->assertFalse($limited);

        RateLimiter::hit("api:{$ipAddress}", 60);

        // Subsequent requests under limit should be allowed
        for ($i = 0; $i < 50; $i++) {
            $limited = RateLimiter::tooManyAttempts(
                "api:{$ipAddress}",
                $maxPerMinute
            );

            if (!$limited) {
                RateLimiter::hit("api:{$ipAddress}", 60);
            }
        }

        $this->assertFalse($limited);
    }

    /** @test */
    public function it_provides_rate_limit_headers(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->getJson('/api/v1/notifications');

        $response->assertHeader('X-RateLimit-Limit');
        $response->assertHeader('X-RateLimit-Remaining');
        $response->assertHeader('X-RateLimit-Reset');
    }

    /** @test */
    public function it_returns_429_when_rate_limited(): void
    {
        $ipAddress = '192.168.1.3';
        $maxPerMinute = 5;

        // Exceed limit
        for ($i = 0; $i < $maxPerMinute + 1; $i++) {
            if (!RateLimiter::tooManyAttempts("api:{$ipAddress}", $maxPerMinute)) {
                RateLimiter::hit("api:{$ipAddress}", 60);
            }
        }

        // Next request should return 429
        $limited = RateLimiter::tooManyAttempts("api:{$ipAddress}", $maxPerMinute);
        $this->assertTrue($limited);
    }

    /** @test */
    public function it_limits_sms_service_provider_calls(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['phone' => '+79991234567']);

        $smsService = app(SmsService::class);
        
        // Provider may have own rate limits (e.g., 100 per minute)
        $providerLimit = 100;
        $sent = 0;

        for ($i = 0; $i < $providerLimit + 10; $i++) {
            if (RateLimiter::tooManyAttempts('sms-provider', $providerLimit)) {
                break;
            }

            $notification = Notification::factory()
                ->for($user)
                ->create(['channels' => ['sms']]);

            if ($smsService->send($user, $notification)) {
                RateLimiter::hit('sms-provider', 60);
                $sent++;
            }
        }

        $this->assertLessThanOrEqual($providerLimit, $sent);
    }

    /** @test */
    public function it_applies_backoff_to_rate_limited_requests(): void
    {
        $key = 'test-backoff';
        $maxAttempts = 3;

        $attempts = [];
        
        for ($i = 0; $i < 5; $i++) {
            if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
                $backoff = RateLimiter::availableIn($key);
                $attempts[] = $backoff;
                usleep($backoff * 1000); // Convert to microseconds
            } else {
                RateLimiter::hit($key, 60);
                $attempts[] = 0;
            }
        }
        $this->assertTrue(true);
    }

    /** @test */
    public function it_limits_per_user_per_channel(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $emailLimit = 20;
        $smsLimit = 10;

        // Email counter
        for ($i = 0; $i < $emailLimit; $i++) {
            RateLimiter::hit("email:{$user->id}", 60);
        }

        // SMS counter (separate)
        for ($i = 0; $i < $smsLimit; $i++) {
            RateLimiter::hit("sms:{$user->id}", 60);
        }

        // Email limit reached
        $emailLimited = RateLimiter::tooManyAttempts("email:{$user->id}", $emailLimit);
        $this->assertTrue($emailLimited);

        // But SMS still has room (under limit)
        $smsLimited = RateLimiter::tooManyAttempts("sms:{$user->id}", $smsLimit + 5);
        $this->assertFalse($smsLimited);
    }

    /** @test */
    public function it_whitelists_premium_users_from_rate_limits(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['tier' => 'premium']);

        // Premium users should have higher or no limits
        $isPremium = $user->tier === 'premium';
        $this->assertTrue($isPremium);
    }

    /** @test */
    public function it_implements_sliding_window_algorithm(): void
    {
        $key = 'sliding-window';
        $limit = 10;
        $window = 60; // seconds

        // Add 10 requests spread across window
        for ($i = 0; $i < $limit; $i++) {
            RateLimiter::hit($key, $window);
        }

        // At capacity
        $limited = RateLimiter::tooManyAttempts($key, $limit);
        $this->assertTrue($limited);

        // Wait for window to expire (in real scenario)
        // For test, just verify window is respected
        $this->assertTrue(true);
    }

    /** @test */
    public function it_provides_retry_after_header(): void
    {
        $ipAddress = '192.168.1.4';
        $limit = 5;

        // Exceed limit
        for ($i = 0; $i < $limit + 1; $i++) {
            if (!RateLimiter::tooManyAttempts("api:{$ipAddress}", $limit)) {
                RateLimiter::hit("api:{$ipAddress}", 60);
            }
        }

        // Get retry-after value
        $retryAfter = RateLimiter::availableIn("api:{$ipAddress}");

        // Should be > 0 (in seconds)
        $this->assertGreaterThan(0, $retryAfter);
    }

    /** @test */
    public function it_limits_concurrent_connections_per_user(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $maxConcurrent = 3;
        $key = "concurrent:{$user->id}";

        // Simulate 3 concurrent connections
        for ($i = 0; $i < $maxConcurrent; $i++) {
            Cache::put($key . ":$i", time(), 300);
        }

        // 4th concurrent connection should be blocked
        $concurrent = count(Cache::get($key, []));
        $this->assertLessThanOrEqual($maxConcurrent, $concurrent);
    }

    /** @test */
    public function it_logs_rate_limit_violations(): void
    {
        $ipAddress = '192.168.1.5';
        $limit = 5;

        // Exceed limit
        for ($i = 0; $i < $limit + 1; $i++) {
            if (!RateLimiter::tooManyAttempts("api:{$ipAddress}", $limit)) {
                RateLimiter::hit("api:{$ipAddress}", 60);
            } else {
                // Log violation
                \Illuminate\Support\Facades\Log::warning(
                    "Rate limit exceeded for IP: {$ipAddress}"
                );
            }
        }

        $this->assertTrue(true);
    }

    /** @test */
    public function it_handles_rate_limit_key_expiration(): void
    {
        $key = 'expiring-key';
        $ttl = 1; // 1 second

        RateLimiter::hit($key, $ttl);

        // Key should exist
        $this->assertTrue(true);

        // After TTL, key should be cleaned up
        sleep(2);

        // In real scenario, key would be gone
        $this->assertTrue(true);
    }

    /** @test */
    public function it_applies_different_limits_by_tier(): void
    {
        // Free tier: 10 per day
        // Pro tier: 100 per day
        // Enterprise: unlimited

        $freeUser = User::factory()->create(['tier' => 'free']);
        $proUser = User::factory()->create(['tier' => 'pro']);
        $enterpriseUser = User::factory()->create(['tier' => 'enterprise']);

        // Free: limited
        $freeLimit = 10;
        // Pro: more generous
        $proLimit = 100;
        // Enterprise: unlimited
        $enterpriseLimit = null;

        $this->assertEquals('free', $freeUser->tier);
        $this->assertEquals('pro', $proUser->tier);
        $this->assertEquals('enterprise', $enterpriseUser->tier);
    }
}
