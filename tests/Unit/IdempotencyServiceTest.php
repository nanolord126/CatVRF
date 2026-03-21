<?php declare(strict_types=1);

namespace Tests\Unit;

use App\Models\PaymentIdempotencyRecord;
use App\Services\Payment\IdempotencyService;
use Illuminate\Support\Str;
use Tests\TestCase;

class IdempotencyServiceTest extends TestCase
{
    private IdempotencyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(IdempotencyService::class);
    }

    public function test_first_request_is_allowed(): void
    {
        $result = $this->service->check(
            'payment_init',
            'test-key-1',
            Str::random(64),
            '{"amount": 10000}'
        );

        $this->assertTrue($result['allowed']);
        $this->assertNull($result['cached_response']);
    }

    public function test_duplicate_request_is_rejected(): void
    {
        $idempotencyKey = Str::random(32);
        $payloadHash = hash('sha256', json_encode(['amount' => 10000]));

        // First request
        $this->service->check('payment_init', $idempotencyKey, $payloadHash, json_encode(['amount' => 10000]));

        // Duplicate request
        $result = $this->service->check('payment_init', $idempotencyKey, $payloadHash, json_encode(['amount' => 10000]));

        $this->assertFalse($result['allowed']);
    }

    public function test_different_payload_with_same_key_is_rejected(): void
    {
        $idempotencyKey = 'same-key';
        $payload1Hash = hash('sha256', '{"amount": 10000}');
        $payload2Hash = hash('sha256', '{"amount": 20000}');

        $this->service->check('payment_init', $idempotencyKey, $payload1Hash, '{"amount": 10000}');

        // Different payload with same key = conflict
        $result = $this->service->check('payment_init', $idempotencyKey, $payload2Hash, '{"amount": 20000}');

        $this->assertFalse($result['allowed']);
    }

    public function test_cached_response_is_returned(): void
    {
        $idempotencyKey = 'cached-key';
        $payloadHash = hash('sha256', '{"amount": 10000}');
        $cachedResponse = ['transaction_id' => 'txn_12345', 'status' => 'pending'];

        PaymentIdempotencyRecord::create([
            'operation' => 'payment_init',
            'idempotency_key' => $idempotencyKey,
            'payload_hash' => $payloadHash,
            'response_data' => json_encode($cachedResponse),
            'expires_at' => now()->addHours(24),
        ]);

        $result = $this->service->check('payment_init', $idempotencyKey, $payloadHash, '{"amount": 10000}');

        $this->assertFalse($result['allowed']);
        $this->assertEquals($cachedResponse, $result['cached_response']);
    }

    public function test_expired_record_allows_retry(): void
    {
        $idempotencyKey = 'expired-key';
        $payloadHash = hash('sha256', '{"amount": 10000}');

        PaymentIdempotencyRecord::create([
            'operation' => 'payment_init',
            'idempotency_key' => $idempotencyKey,
            'payload_hash' => $payloadHash,
            'response_data' => json_encode(['old' => 'response']),
            'expires_at' => now()->subMinute(),
        ]);

        $result = $this->service->check('payment_init', $idempotencyKey, $payloadHash, '{"amount": 10000}');

        $this->assertTrue($result['allowed']);
    }

    public function test_store_response_saves_correctly(): void
    {
        $idempotencyKey = 'store-key';
        $payloadHash = hash('sha256', '{"amount": 10000}');
        $response = ['transaction_id' => 'txn_99999', 'status' => 'authorized'];

        $this->service->storeResponse('payment_init', $idempotencyKey, $payloadHash, $response);

        $record = PaymentIdempotencyRecord::where('idempotency_key', $idempotencyKey)->first();

        $this->assertNotNull($record);
        $this->assertEquals($payloadHash, $record->payload_hash);
        $this->assertEquals(json_encode($response), $record->response_data);
    }

    public function test_multiple_different_keys_are_independent(): void
    {
        $key1 = 'key-1';
        $key2 = 'key-2';
        $hash = hash('sha256', '{"amount": 10000}');

        $result1 = $this->service->check('payment_init', $key1, $hash, '{"amount": 10000}');
        $result2 = $this->service->check('payment_init', $key2, $hash, '{"amount": 10000}');

        $this->assertTrue($result1['allowed']);
        $this->assertTrue($result2['allowed']);
    }

    public function test_cleanup_removes_expired(): void
    {
        PaymentIdempotencyRecord::create([
            'operation' => 'test',
            'idempotency_key' => 'old-1',
            'payload_hash' => hash('sha256', 'test'),
            'response_data' => '{}',
            'expires_at' => now()->subDay(),
        ]);

        PaymentIdempotencyRecord::create([
            'operation' => 'test',
            'idempotency_key' => 'new-1',
            'payload_hash' => hash('sha256', 'test'),
            'response_data' => '{}',
            'expires_at' => now()->addDay(),
        ]);

        $this->assertEquals(2, PaymentIdempotencyRecord::count());

        $this->service->cleanupExpired();

        $this->assertEquals(1, PaymentIdempotencyRecord::count());
        $this->assertNotNull(PaymentIdempotencyRecord::where('idempotency_key', 'new-1')->first());
    }
}
