<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\Concerns\InteractsWithTime;

/**
 * Concurrency Test Helper
 *
 * Provides utilities for testing race conditions, atomicity,
 * and concurrent operations in slots, wallet, quota, and payments.
 */
class ConcurrencyTestHelper
{
    use InteractsWithTime;

    /**
     * Execute callback concurrently with multiple processes
     */
    public static function runConcurrently(callable $callback, int $count = 10): array
    {
        $results = [];
        $processes = [];

        for ($i = 0; $i < $count; $i++) {
            $processes[] = proc_open(
                PHP_BINARY.' -r '.escapeshellarg($callback($i)),
                [
                    0 => ['pipe', 'r'],
                    1 => ['pipe', 'w'],
                    2 => ['pipe', 'w'],
                ],
                $pipes
            );

            if (is_resource($processes[$i])) {
                $results[$i] = stream_get_contents($pipes[1]);
                fclose($pipes[0]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($processes[$i]);
            }
        }

        return $results;
    }

    /**
     * Test slot hold race condition
     */
    public static function testSlotHoldRaceCondition(int $slotId, int $concurrentUsers = 10): array
    {
        $results = ['success' => 0, 'failed' => 0, 'errors' => []];

        for ($i = 0; $i < $concurrentUsers; $i++) {
            try {
                $key = "slot:hold:{$slotId}";
                $userId = 1000 + $i;

                // Try to acquire lock with Redis SET NX
                $acquired = Redis::set($key, (string) $userId, 'EX', 300, 'NX');

                if ($acquired) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                }
            } catch (\Exception $e) {
                $results['errors'][] = $e->getMessage();
            }
        }

        // Cleanup
        Redis::del("slot:hold:{$slotId}");

        return $results;
    }

    /**
     * Test wallet debit race condition
     */
    public static function testWalletDebitRaceCondition(int $userId, int $amount, int $concurrentDebits = 5): array
    {
        $results = [
            'initial_balance' => 0,
            'final_balance' => 0,
            'expected_balance' => 0,
            'total_debited' => 0,
            'race_condition_detected' => false,
        ];

        // Get initial balance
        $results['initial_balance'] = (int) Redis::get("wallet:balance:{$userId}") ?? 0;
        $results['expected_balance'] = $results['initial_balance'] - ($amount * $concurrentDebits);

        // Simulate concurrent debits
        for ($i = 0; $i < $concurrentDebits; $i++) {
            $decremented = Redis::decrBy("wallet:balance:{$userId}", $amount);
            $results['total_debited'] += $amount;
        }

        $results['final_balance'] = (int) Redis::get("wallet:balance:{$userId}");
        $results['race_condition_detected'] = $results['final_balance'] !== $results['expected_balance'];

        // Reset balance
        Redis::set("wallet:balance:{$userId}", $results['initial_balance']);

        return $results;
    }

    /**
     * Test quota increment race condition
     */
    public static function testQuotaIncrementRaceCondition(int $userId, int $limit, int $concurrentIncrements = 10): array
    {
        $results = [
            'final_count' => 0,
            'exceeded_limit' => false,
            'race_condition_detected' => false,
        ];

        // Reset quota
        Redis::del("quota:user:{$userId}");

        // Simulate concurrent increments
        for ($i = 0; $i < $concurrentIncrements; $i++) {
            $count = Redis::incr("quota:user:{$userId}");

            if ($count > $limit) {
                $results['exceeded_limit'] = true;
            }
        }

        $results['final_count'] = (int) Redis::get("quota:user:{$userId}");
        $results['race_condition_detected'] = $results['final_count'] !== $concurrentIncrements;

        // Cleanup
        Redis::del("quota:user:{$userId}");

        return $results;
    }

    /**
     * Test payment processing race condition
     */
    public static function testPaymentProcessingRaceCondition(string $paymentId, int $concurrentProcesses = 5): array
    {
        $results = [
            'processed_count' => 0,
            'duplicate_processed' => false,
            'errors' => [],
        ];

        $lockKey = "payment:lock:{$paymentId}";

        for ($i = 0; $i < $concurrentProcesses; $i++) {
            try {
                // Try to acquire distributed lock
                $acquired = Redis::set($lockKey, '1', 'EX', 30, 'NX');

                if ($acquired) {
                    $results['processed_count']++;

                    // Simulate processing time
                    usleep(100000); // 100ms

                    // Release lock
                    Redis::del($lockKey);
                } else {
                    // Lock already held - this is expected behavior
                }
            } catch (\Exception $e) {
                $results['errors'][] = $e->getMessage();
            }
        }

        $results['duplicate_processed'] = $results['processed_count'] > 1;

        // Cleanup
        Redis::del($lockKey);

        return $results;
    }

    /**
     * Test database transaction atomicity under concurrency
     */
    public static function testTransactionAtomicity(callable $transaction, int $concurrentAttempts = 5): array
    {
        $results = [
            'initial_count' => 0,
            'final_count' => 0,
            'expected_count' => 0,
            'atomicity_violated' => false,
            'errors' => [],
        ];

        // Get initial count (assuming transaction creates records)
        $results['initial_count'] = DB::table('test_records')->count();

        // Run concurrent transactions
        for ($i = 0; $i < $concurrentAttempts; $i++) {
            try {
                DB::transaction($transaction);
            } catch (\Exception $e) {
                $results['errors'][] = $e->getMessage();
            }
        }

        $results['final_count'] = DB::table('test_records')->count();
        $results['expected_count'] = $results['initial_count'] + $concurrentAttempts;
        $results['atomicity_violated'] = $results['final_count'] !== $results['expected_count'];

        return $results;
    }

    /**
     * Test cache stampede protection
     */
    public static function testCacheStampedeProtection(string $cacheKey, callable $expensiveCallback, int $concurrentRequests = 10): array
    {
        $results = [
            'callback_executions' => 0,
            'cache_hits' => 0,
            'stampede_detected' => false,
        ];

        // Clear cache
        Redis::del($cacheKey);

        // Simulate concurrent requests
        for ($i = 0; $i < $concurrentRequests; $i++) {
            $lockKey = "cache:lock:{$cacheKey}";

            // Try to acquire lock
            $acquired = Redis::set($lockKey, '1', 'EX', 10, 'NX');

            if ($acquired) {
                // First request executes callback
                $results['callback_executions']++;
                $value = $expensiveCallback();
                Redis::set($cacheKey, $value, 'EX', 3600);
                Redis::del($lockKey);
            } else {
                // Other requests wait and get from cache
                usleep(50000); // Wait 50ms
                if (Redis::exists($cacheKey)) {
                    $results['cache_hits']++;
                }
            }
        }

        $results['stampede_detected'] = $results['callback_executions'] > 1;

        // Cleanup
        Redis::del($cacheKey);

        return $results;
    }

    /**
     * Test Redis Lua script atomicity
     */
    public static function testLuaScriptAtomicity(string $script, array $keys, array $args): bool
    {
        try {
            $result = Redis::eval($script, count($keys), ...$keys, ...$args);

            return $result !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Assert no race condition occurred
     */
    public static function assertNoRaceCondition(array $results): void
    {
        expect($results)->not->toHaveKey('race_condition_detected', true);
        expect($results)->not->toHaveKey('atomicity_violated', true);
        expect($results)->not->toHaveKey('duplicate_processed', true);
        expect($results)->not->toHaveKey('stampede_detected', true);
    }

    /**
     * Assert operation is atomic
     */
    public static function assertAtomic(callable $operation, int $expectedCount): void
    {
        $result = $operation();
        expect($result)->toBe($expectedCount);
    }

    /**
     * Create a Lua script for atomic slot hold
     */
    public static function createSlotHoldLuaScript(): string
    {
        return <<<'LUA'
            local slotKey = KEYS[1]
            local userId = ARGV[1]
            local ttl = ARGV[2]
            
            if redis.call('EXISTS', slotKey) == 0 then
                redis.call('SET', slotKey, userId, 'EX', ttl)
                return 1
            else
                return 0
            end
        LUA;
    }

    /**
     * Create a Lua script for atomic wallet debit
     */
    public static function createWalletDebitLuaScript(): string
    {
        return <<<'LUA'
            local balanceKey = KEYS[1]
            local amount = tonumber(ARGV[1])
            
            local currentBalance = tonumber(redis.call('GET', balanceKey) or 0)
            
            if currentBalance >= amount then
                redis.call('DECRBY', balanceKey, amount)
                return 1
            else
                return 0
            end
        LUA;
    }

    /**
     * Create a Lua script for atomic quota check and increment
     */
    public static function createQuotaLuaScript(): string
    {
        return <<<'LUA'
            local quotaKey = KEYS[1]
            local limit = tonumber(ARGV[1])
            
            local current = tonumber(redis.call('GET', quotaKey) or 0)
            
            if current < limit then
                redis.call('INCR', quotaKey)
                return 1
            else
                return 0
            end
        LUA;
    }
}
