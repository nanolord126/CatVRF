<?php
declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Services\Security\IdempotencyService;
use App\Services\Security\RateLimiterService;
use App\Services\Security\WebhookSignatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

final class SecurityIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private IdempotencyService $idempotencyService;
    private RateLimiterService $rateLimiterService;
    private WebhookSignatureService $webhookService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->idempotencyService = app(IdempotencyService::class);
        $this->rateLimiterService = app(RateLimiterService::class);
        $this->webhookService = app(WebhookSignatureService::class);
        
        Redis::flush();
    }

    public function test_idempotency_service_detects_duplicates(): void
    {
        $payload = json_encode(['amount' => 50000, 'order_id' => 123]);
        $idempotencyKey = 'test-key-' . time();
        
        $check1 = $this->idempotencyService->check($idempotencyKey, $payload);
        $this->assertTrue($check1);
        
        $check2 = $this->idempotencyService->check($idempotencyKey, $payload);
        $this->assertFalse($check2, 'Duplicate request should be detected');
    }

    public function test_rate_limiter_blocks_excessive_payment_requests(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $allowed = $this->rateLimiterService->checkPaymentInit(123, '192.168.1.1');
            $this->assertTrue($allowed);
        }
        
        $blocked = $this->rateLimiterService->checkPaymentInit(123, '192.168.1.1');
        $this->assertFalse($blocked, 'Should block after 10 requests in 1 minute');
    }

    public function test_webhook_signature_verification_passes(): void
    {
        $payload = json_encode(['amount' => 50000]);
        $secret = config('security.webhook_secrets.tinkoff');
        $signature = hash_hmac('sha256', $payload, $secret);
        
        $result = $this->webhookService->verify('tinkoff', $payload, $signature);
        $this->assertTrue($result);
    }

    public function test_webhook_signature_verification_fails_on_tampered_payload(): void
    {
        $payload = json_encode(['amount' => 50000]);
        $secret = config('security.webhook_secrets.tinkoff');
        $signature = hash_hmac('sha256', $payload, $secret);
        
        $tamperedPayload = json_encode(['amount' => 100000]);
        $result = $this->webhookService->verify('tinkoff', $tamperedPayload, $signature);
        $this->assertFalse($result);
    }
}
