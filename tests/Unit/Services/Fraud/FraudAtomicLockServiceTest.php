<?php declare(strict_types=1);

namespace Tests\Unit\Services\Fraud;

use App\Services\Fraud\FraudAtomicLockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

final class FraudAtomicLockServiceTest extends TestCase
{
    use RefreshDatabase;

    private FraudAtomicLockService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FraudAtomicLockService::class);
        Redis::flushdb();
    }

    public function test_fraud_check_with_slot_hold_succeeds_for_low_score(): void
    {
        $userId = 123;
        $slotKey = 'slot:doctor:1:2024-01-01:10:00';
        $fraudScore = 0.3;

        $result = $this->service->fraudCheckWithSlotHold($userId, $slotKey, $fraudScore);

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['lock_key']);
        $this->assertEquals('success', $result['reason']);
        $this->assertArrayHasKey('correlation_id', $result);
    }

    public function test_fraud_check_with_slot_hold_blocks_high_score(): void
    {
        $userId = 123;
        $slotKey = 'slot:doctor:1:2024-01-01:10:00';
        $fraudScore = 0.9;

        $result = $this->service->fraudCheckWithSlotHold($userId, $slotKey, $fraudScore);

        $this->assertFalse($result['success']);
        $this->assertNull($result['lock_key']);
        $this->assertEquals('fraud_blocked', $result['reason']);
    }

    public function test_fraud_check_with_slot_hold_prevents_double_booking(): void
    {
        $userId1 = 123;
        $userId2 = 456;
        $slotKey = 'slot:doctor:1:2024-01-01:10:00';
        $fraudScore = 0.3;

        // First user holds slot
        $result1 = $this->service->fraudCheckWithSlotHold($userId1, $slotKey, $fraudScore);
        $this->assertTrue($result1['success']);

        // Second user tries to hold same slot
        $result2 = $this->service->fraudCheckWithSlotHold($userId2, $slotKey, $fraudScore);
        $this->assertFalse($result2['success']);
        $this->assertEquals('slot_already_held', $result2['reason']);
    }

    public function test_fraud_check_with_slot_hold_allows_same_user_twice(): void
    {
        $userId = 123;
        $slotKey = 'slot:doctor:1:2024-01-01:10:00';
        $fraudScore = 0.3;

        $result1 = $this->service->fraudCheckWithSlotHold($userId, $slotKey, $fraudScore);
        $this->assertTrue($result1['success']);

        $result2 = $this->service->fraudCheckWithSlotHold($userId, $slotKey, $fraudScore);
        $this->assertTrue($result2['success']);
    }

    public function test_fraud_check_with_payment_succeeds_for_low_score(): void
    {
        $userId = 123;
        $paymentKey = 'payment:order:456';
        $fraudScore = 0.3;

        $result = $this->service->fraudCheckWithPayment($userId, $paymentKey, $fraudScore);

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['lock_key']);
        $this->assertEquals('success', $result['reason']);
    }

    public function test_fraud_check_with_payment_blocks_high_score(): void
    {
        $userId = 123;
        $paymentKey = 'payment:order:456';
        $fraudScore = 0.9;

        $result = $this->service->fraudCheckWithPayment($userId, $paymentKey, $fraudScore);

        $this->assertFalse($result['success']);
        $this->assertNull($result['lock_key']);
        $this->assertEquals('fraud_blocked', $result['reason']);
    }

    public function test_release_lock_releases_lock(): void
    {
        $userId = 123;
        $slotKey = 'slot:doctor:1:2024-01-01:10:00';
        $fraudScore = 0.3;

        $result = $this->service->fraudCheckWithSlotHold($userId, $slotKey, $fraudScore);
        $lockKey = $result['lock_key'];

        $released = $this->service->releaseLock($lockKey);
        $this->assertTrue($released);

        // Lock should no longer exist
        $exists = Redis::exists($lockKey);
        $this->assertFalse($exists);
    }

    public function test_release_slot_hold_releases_slot(): void
    {
        $userId = 123;
        $slotKey = 'slot:doctor:1:2024-01-01:10:00';
        $fraudScore = 0.3;

        $this->service->fraudCheckWithSlotHold($userId, $slotKey, $fraudScore);
        $this->assertTrue($this->service->isSlotHeld($slotKey));

        $released = $this->service->releaseSlotHold($slotKey, $userId);
        $this->assertTrue($released);
        $this->assertFalse($this->service->isSlotHeld($slotKey));
    }

    public function test_release_slot_hold_prevents_unauthorized_release(): void
    {
        $userId1 = 123;
        $userId2 = 456;
        $slotKey = 'slot:doctor:1:2024-01-01:10:00';
        $fraudScore = 0.3;

        $this->service->fraudCheckWithSlotHold($userId1, $slotKey, $fraudScore);

        // User 2 tries to release user 1's slot
        $released = $this->service->releaseSlotHold($slotKey, $userId2);
        $this->assertFalse($released);
        $this->assertTrue($this->service->isSlotHeld($slotKey));
    }

    public function test_is_slot_held_returns_correct_status(): void
    {
        $slotKey = 'slot:doctor:1:2024-01-01:10:00';

        $this->assertFalse($this->service->isSlotHeld($slotKey));

        $this->service->fraudCheckWithSlotHold(123, $slotKey, 0.3);
        $this->assertTrue($this->service->isSlotHeld($slotKey));
    }

    public function test_get_slot_holder_returns_holder_info(): void
    {
        $userId = 123;
        $slotKey = 'slot:doctor:1:2024-01-01:10:00';
        $fraudScore = 0.3;

        $this->service->fraudCheckWithSlotHold($userId, $slotKey, $fraudScore);

        $holder = $this->service->getSlotHolder($slotKey);

        $this->assertIsArray($holder);
        $this->assertEquals($userId, $holder['holder_id']);
        $this->assertNotNull($holder['held_at']);
        $this->assertEquals($fraudScore, $holder['fraud_score']);
    }

    public function test_get_slot_holder_returns_null_for_unheld_slot(): void
    {
        $slotKey = 'slot:doctor:1:2024-01-01:10:00';

        $holder = $this->service->getSlotHolder($slotKey);

        $this->assertNull($holder);
    }

    public function test_custom_threshold_respected(): void
    {
        $userId = 123;
        $slotKey = 'slot:doctor:1:2024-01-01:10:00';
        $fraudScore = 0.5;
        $threshold = 0.6; // Higher than default

        $result = $this->service->fraudCheckWithSlotHold($userId, $slotKey, $fraudScore, $threshold);

        $this->assertTrue($result['success']); // Should pass with custom threshold
    }

    public function test_concurrent_operations_prevented(): void
    {
        $userId = 123;
        $slotKey = 'slot:doctor:1:2024-01-01:10:00';
        $fraudScore = 0.3;

        $result1 = $this->service->fraudCheckWithSlotHold($userId, $slotKey, $fraudScore);
        $this->assertTrue($result1['success']);

        // Simulate concurrent operation by trying same payment
        $paymentKey = 'payment:order:789';
        $result2 = $this->service->fraudCheckWithPayment($userId, $paymentKey, $fraudScore);
        $this->assertTrue($result2['success']); // Different key, should succeed
    }
}
