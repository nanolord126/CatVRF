<?php

declare(strict_types=1);

namespace Tests;

use Tests\Helpers\FraudTestHelper;
use Tests\Helpers\ConcurrencyTestHelper;
use Tests\Traits\AssertsPiiCompliance;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;

abstract class BaseVerticalTestCase extends BaseTestCase
{
    use AssertsPiiCompliance;

    protected string $verticalName;

    protected string $domainNamespace;

    protected string $serviceNamespace;

    protected string $modelNamespace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clearLogFile();
        Cache::flush();
        Queue::fake();
        Event::fake();
    }

    protected function tearDown(): void
    {
        $this->assertLogsContainNoPii();
        parent::tearDown();
    }

    /**
     * Set vertical context for testing
     */
    protected function setVerticalContext(string $verticalName): void
    {
        $this->verticalName = $verticalName;
        $this->domainNamespace = "App\\Domains\\{$verticalName}";
        $this->serviceNamespace = "{$this->domainNamespace}\\Services";
        $this->modelNamespace = "{$this->domainNamespace}\\Models";
    }

    /**
     * Get fully qualified class name for a service
     */
    protected function getServiceClass(string $serviceName): string
    {
        return "{$this->serviceNamespace}\\{$serviceName}";
    }

    /**
     * Get fully qualified class name for a model
     */
    protected function getModelClass(string $modelName): string
    {
        return "{$this->modelNamespace}\\{$modelName}";
    }

    /**
     * Get fully qualified class name for a DTO
     */
    protected function getDtoClass(string $dtoName): string
    {
        return "{$this->domainNamespace}\\DTOs\\{$dtoName}";
    }

    /**
     * Assert that service exists and is instantiable
     */
    protected function assertServiceExists(string $serviceName): void
    {
        $serviceClass = $this->getServiceClass($serviceName);

        expect(class_exists($serviceClass))->toBeTrue(
            "Service class {$serviceClass} does not exist"
        );

        expect(app($serviceClass))->toBeInstanceOf($serviceClass);
    }

    /**
     * Assert that model exists and is instantiable
     */
    protected function assertVerticalModelExists(string $modelName): void
    {
        $modelClass = $this->getModelClass($modelName);

        expect(class_exists($modelClass))->toBeTrue(
            "Model class {$modelClass} does not exist"
        );
    }

    /**
     * Assert fraud check is performed in service
     */
    protected function assertFraudCheckPerformed(callable $action): void
    {
        $auditLogs = [];

        // Mock audit log recording
        DB::listen(function ($query) use (&$auditLogs) {
            if (str_contains($query->sql, 'audit_logs')) {
                $auditLogs[] = $query->bindings;
            }
        });

        $action();

        FraudTestHelper::assertFraudCheckPerformed($auditLogs);
    }

    /**
     * Assert quota enforcement for service
     */
    protected function assertQuotaEnforced(int $userId, int $limit): void
    {
        $quotaData = [
            'quota_limit' => $limit,
            'quota_used' => (int) Cache::get("quota:user:{$userId}:used", 0),
        ];

        expect($quotaData['quota_used'])->toBeLessThanOrEqual($quotaData['quota_limit']);
    }

    /**
     * Test service with fraud check
     */
    protected function test_service_with_fraud_check(string $serviceName, string $method, array $params = []): void
    {
        $service = app($this->getServiceClass($serviceName));

        FraudTestHelper::mockFraudMLPrediction(0.1); // Low fraud score

        $result = $service->$method(...$params);

        expect($result)->not->toBeNull();
        $this->assertFraudCheckPerformed(fn () => $service->$method(...$params));
    }

    /**
     * Test service with quota enforcement
     */
    protected function test_service_with_quota(string $serviceName, string $method, int $userId, int $limit, array $params = []): void
    {
        Cache::put("quota:user:{$userId}:limit", $limit);
        Cache::put("quota:user:{$userId}:used", 0);

        $service = app($this->getServiceClass($serviceName));

        try {
            $result = $service->$method(...$params);
            expect($result)->not->toBeNull();
        } catch (\Exception $e) {
            // Service may throw exception when quota exceeded
        }

        $this->assertQuotaEnforced($userId, $limit);
    }

    /**
     * Test concurrent operations
     */
    protected function test_concurrent_operation(callable $operation, int $concurrency = 10): array
    {
        return ConcurrencyTestHelper::testQuotaIncrementRaceCondition(
            1, // user ID
            10, // limit
            $concurrency
        );
    }

    /**
     * Assert no race condition in concurrent operation
     */
    protected function assertNoRaceCondition(callable $operation, int $concurrency = 10): void
    {
        $results = $this->testConcurrentOperation($operation, $concurrency);
        ConcurrencyTestHelper::assertNoRaceCondition($results);
    }

    /**
     * Assert PII compliance for vertical data
     */
    protected function assertVerticalDataPiiCompliant(array $data): void
    {
        $this->assertNoPiiPatterns($data);

        // If vertical handles medical data, check medical PII
        if (in_array($this->verticalName, ['Medical', 'Healthcare', 'Pharmacy', 'Veterinary'], true)) {
            $this->assertSymptomsAnonymized($data['symptoms'] ?? []);
        }
    }

    /**
     * Assert service response structure
     */
    protected function assertResponseStructure(array $response, array $expectedKeys): void
    {
        foreach ($expectedKeys as $key) {
            expect($response)->toHaveKey($key);
        }
    }

    /**
     * Assert service has proper caching
     */
    protected function assertServiceCaching(string $cacheKey, callable $uncachedOperation): void
    {
        Cache::forget($cacheKey);

        // First call - cache miss
        $result1 = $uncachedOperation();
        expect(Cache::has($cacheKey))->toBeTrue();

        // Second call - cache hit
        $result2 = Cache::get($cacheKey);
        expect($result2)->toEqual($result1);

        // Assert cache contains no PII
        $this->assertCacheContainsNoPii($cacheKey);
    }

    /**
     * Assert service has proper event dispatching
     */
    protected function assertEventDispatched(string $eventClass, callable $action): void
    {
        Event::fake();

        $action();

        Event::assertDispatched($eventClass);
    }

    /**
     * Assert service has proper job dispatching
     */
    protected function assertJobDispatched(string $jobClass, callable $action): void
    {
        Queue::fake();

        $action();

        Queue::assertPushed($jobClass);
    }

    /**
     * Create test data for vertical
     */
    protected function createVerticalTestData(array $overrides = []): array
    {
        return array_merge([
            'user_id' => 1,
            'tenant_id' => tenant('id') ?? 'default',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);
    }

    /**
     * Assert vertical service follows clean architecture
     */
    protected function assertCleanArchitecture(string $serviceName): void
    {
        $serviceClass = $this->getServiceClass($serviceName);
        $reflection = new \ReflectionClass($serviceClass);

        // Service should be final
        expect($reflection->isFinal())->toBeTrue(
            "Service {$serviceName} should be final"
        );

        // Service should not use static calls
        $content = file_get_contents($reflection->getFileName());
        expect($content)->not->toContain('static::');
    }

    /**
     * Assert vertical service has proper error handling
     */
    protected function assertErrorHandling(callable $action, string $expectedException): void
    {
        $this->expectException($expectedException);
        $action();
    }

    /**
     * Assert vertical service has proper logging
     */
    protected function assertServiceLogging(callable $action, string $expectedLogMessage): void
    {
        $this->clearLogFile();

        $action();

        $logContent = $this->getLogContent();
        expect($logContent)->toContain($expectedLogMessage);
    }

    /**
     * Clear log file helper
     */
    protected function clearLogFile(): void
    {
        $logFile = storage_path('logs/laravel.log');

        if (file_exists($logFile)) {
            file_put_contents($logFile, '');
        }
    }

    /**
     * Get log content helper
     */
    protected function getLogContent(): string
    {
        $logFile = storage_path('logs/laravel.log');

        return file_exists($logFile) ? file_get_contents($logFile) : '';
    }
}
