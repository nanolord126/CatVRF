<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

final class GenerateVerticalTestsCommand extends Command
{
    public function __construct(
        private readonly Filesystem $files,
    ) {
        parent::__construct();
    }
    protected $signature = 'vertical:tests {vertical : The vertical name}
                           {--type=all : Test type (unit, feature, critical, all)}
                           {--force : Overwrite existing tests}';

    protected $description = 'Generate Pest tests for a vertical with PII and concurrency support';

    private array $verticals = [
        'Medical', 'Payment', 'FraudML', 'Food', 'RealEstate', 'Travel', 'Auto',
        'Hotels', 'Electronics', 'Fitness', 'Sports', 'Luxury', 'Insurance', 'Legal',
        'Logistics', 'Education', 'CRM', 'Delivery', 'Analytics', 'Consulting',
        'Content', 'Freelance', 'EventPlanning', 'Staff', 'Inventory', 'Taxi',
        'Tickets', 'Wallet', 'Pet', 'WeddingPlanning', 'Veterinary', 'ToysAndGames',
        'Advertising', 'CarRental', 'Finances', 'Flowers', 'Furniture', 'Pharmacy',
        'Photography', 'ShortTermRentals', 'SportsNutrition', 'PersonalDevelopment',
        'HomeServices', 'Gardening', 'Geo', 'GeoLogistics', 'GroceryAndDelivery',
        'FarmDirect', 'MeatShops', 'OfficeCatering', 'PartySupplies', 'Confectionery',
        'ConstructionAndRepair', 'CleaningServices', 'Communication', 'BooksAndLiterature',
        'Collectibles', 'HobbyAndCraft', 'HouseholdGoods', 'Marketplace',
        'MusicAndInstruments', 'VeganProducts', 'Art', 'Beauty', 'Fashion',
    ];

    public function handle(): int
    {
        $vertical = $this->argument('vertical');
        $type = $this->option('type');
        $force = $this->option('force');

        // Validate vertical name
        if (! in_array($vertical, $this->verticals, true)) {
            $this->error("Vertical '{$vertical}' not found. Available verticals:");
            $this->table(['Vertical'], array_map(fn ($v) => [$v], $this->verticals));

            return Command::FAILURE;
        }

        $this->info("Generating tests for {$vertical} vertical...");

        // Generate tests based on type
        if ($type === 'all' || $type === 'unit') {
            $this->generateUnitTest($vertical, $force);
        }

        if ($type === 'all' || $type === 'feature') {
            $this->generateFeatureTest($vertical, $force);
        }

        if ($type === 'all' || $type === 'critical') {
            $this->generateCriticalPathTest($vertical, $force);
        }

        $this->info("Tests generated successfully for {$vertical}!");

        return Command::SUCCESS;
    }

    private function generateUnitTest(string $vertical, bool $force): void
    {
        $path = $this->laravel->basePath("tests/Unit/Domains/{$vertical}/{$vertical}ServiceTest.php");
        $directory = dirname($path);

        if (! $force && $this->files->exists($path)) {
            $this->warn("Unit test already exists: {$path}");

            return;
        }

        if (! $this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        $content = $this->getUnitTestTemplate($vertical);
        $this->files->put($path, $content);

        $this->info("Generated unit test: {$path}");
    }

    private function generateFeatureTest(string $vertical, bool $force): void
    {
        $path = $this->laravel->basePath("tests/Feature/{$vertical}/{$vertical}ApiTest.php");
        $directory = dirname($path);

        if (! $force && $this->files->exists($path)) {
            $this->warn("Feature test already exists: {$path}");

            return;
        }

        if (! $this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        $content = $this->getFeatureTestTemplate($vertical);
        $this->files->put($path, $content);

        $this->info("Generated feature test: {$path}");
    }

    private function generateCriticalPathTest(string $vertical, bool $force): void
    {
        $path = $this->laravel->basePath("tests/Feature/{$vertical}/{$vertical}CriticalPathTest.php");
        $directory = dirname($path);

        if (! $force && $this->files->exists($path)) {
            $this->warn("Critical path test already exists: {$path}");

            return;
        }

        if (! $this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        $content = $this->getCriticalPathTestTemplate($vertical);
        $this->files->put($path, $content);

        $this->info("Generated critical path test: {$path}");
    }

    private function getUnitTestTemplate(string $vertical): string
    {
        $verticalLower = Str::lower($vertical);
        $serviceName = "{$vertical}Service";

        return <<<PHP
<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\\{$vertical};

use Tests\BaseVerticalTestCase;
use App\Domains\{$vertical}\Services\{$serviceName};
use App\Services\Cache\CacheService;
use App\Services\Security\AuditService;

// Pest test using modern declarative syntax
uses(BaseVerticalTestCase::class);

beforeEach(function () {
    \$this->setVerticalContext('{$vertical}');
});

test('{$serviceName} exists and is instantiable', function () {
    \$this->assertServiceExists('{$serviceName}');
});

test('{$serviceName} follows clean architecture', function () {
    \$this->assertCleanArchitecture('{$serviceName}');
});

test('{$serviceName} performs fraud check', function () {
    \$this->testServiceWithFraudCheck('{$serviceName}', 'process', []);
});

test('{$serviceName} enforces quota limits', function () {
    \$this->testServiceWithQuota('{$serviceName}', 'process', 1, 10, []);
});

test('{$serviceName} handles concurrent operations', function () {
    \$this->assertNoRaceCondition(function () {
        // Simulate concurrent operation
        \$service = \$this->app->make(\$this->getServiceClass('{$serviceName}'));
        \$service->process([]);
    }, 10);
});

test('{$serviceName} has proper caching', function () {
    \$cacheKey = "{$verticalLower}:data:1";
    
    \$this->assertServiceCaching(\$cacheKey, function () {
        \$service = \$this->app->make(\$this->getServiceClass('{$serviceName}'));
        return \$service->getData(1);
    });
});

test('{$serviceName} dispatches proper events', function () {
    \$eventClass = "App\Domains\{$vertical}\Events\{$vertical}Processed";
    
    \$this->assertEventDispatched(\$eventClass, function () {
        \$service = \$this->app->make(\$this->getServiceClass('{$serviceName}'));
        \$service->process([]);
    });
});

test('{$serviceName} dispatches proper jobs', function () {
    \$jobClass = "App\Domains\{$vertical}\Jobs\Process{$vertical}Job";
    
    \$this->assertJobDispatched(\$jobClass, function () {
        \$service = \$this->app->make(\$this->getServiceClass('{$serviceName}'));
        \$service->processAsync([]);
    });
});

test('{$serviceName} handles errors gracefully', function () {
    \$this->assertErrorHandling(function () {
        \$service = \$this->app->make(\$this->getServiceClass('{$serviceName}'));
        \$service->process([]);
    }, \Exception::class);
});

test('{$serviceName} logs operations', function () {
    \$this->assertServiceLogging(function () {
        \$service = \$this->app->make(\$this->getServiceClass('{$serviceName}'));
        \$service->process([]);
    }, '{$serviceName} processed');
});

test('{$serviceName} data is PII compliant', function () {
    \$data = [
        'user_id' => 1,
        'name' => 'Test User',
        'email' => 'test@example.com',
    ];
    
    \$this->assertVerticalDataPiiCompliant(\$data);
});
PHP;
    }

    private function getFeatureTestTemplate(string $vertical): string
    {
        $verticalLower = Str::lower($vertical);
        $routePrefix = Str::kebab($verticalLower);

        return <<<PHP
<?php

declare(strict_types=1);

namespace Tests\Feature\\{$vertical};

use Tests\Helpers\FraudTestHelper;

// Pest test using modern declarative syntax
uses(BaseVerticalTestCase::class);

beforeEach(function () {
    \$this->setVerticalContext('{$vertical}');
    \$this->cacheService = \$this->app->make(CacheService::class);
    \$this->auditService = \$this->app->make(AuditService::class);
});

test('{$vertical} operation with quota exceeded is handled correctly', function () {
    // Arrange: Set quota to exceeded
    \$userId = 1;
    \$this->cacheService->put("quota:user:{$userId}:limit", 10);
    \$this->cacheService->put("quota:user:{$userId}:used", 10);
    
    \$data = \$this->createVerticalTestData(['user_id' => \$userId]);
    
    // Act: Process operation
    \$service = \$this->app->make(\$this->getServiceClass('{$serviceName}'));
    \$quotaCheck = \$service->checkQuota(\$userId);
    
    // Assert: Quota should be exceeded
    expect(\$quotaCheck['allowed'])->toBeFalse();
    expect(\$quotaCheck['reason'])->toBe('quota_exceeded');
});

test('{$vertical} operation with fraud block is handled correctly', function () {
    // Arrange: Create suspicious data
    \$suspiciousData = array_merge(
        \$this->createVerticalTestData(),
        FraudTestHelper::createSuspiciousTransaction()
    );
    
    FraudTestHelper::mockFraudMLPrediction(0.85); // High fraud score
    
    // Act: Check fraud
    \$service = \$this->app->make(\$this->getServiceClass('{$serviceName}'));
    \$fraudCheck = \$service->checkFraud(\$suspiciousData);
    
    // Assert: Operation should be blocked
    expect(\$fraudCheck['is_fraud'])->toBeTrue();
    expect(\$fraudCheck['action'])->toBe('block');
});

test('{$vertical} operation with quota exceeded and fraud block simultaneously', function () {
    // Arrange: Set quota to exceeded and create suspicious data
    \$userId = 1;
    \$this->cacheService->put("quota:user:{$userId}:limit", 10);
    \$this->cacheService->put("quota:user:{$userId}:used", 11);
    
    \$suspiciousData = array_merge(
        \$this->createVerticalTestData(['user_id' => \$userId]),
        FraudTestHelper::createSuspiciousTransaction()
    );
    
    FraudTestHelper::mockFraudMLPrediction(0.92);
    
    // Act: Process operation
    \$service = \$this->app->make(\$this->getServiceClass('{$serviceName}'));
    \$quotaCheck = \$service->checkQuota(\$userId);
    \$fraudCheck = \$service->checkFraud(\$suspiciousData);
    
    // Assert: Both checks should fail
    expect(\$quotaCheck['allowed'])->toBeFalse();
    expect(\$fraudCheck['is_fraud'])->toBeTrue();
});

test('{$vertical} critical operation creates proper audit trail', function () {
    // Arrange
    \$data = \$this->createVerticalTestData();
    
    // Act: Process critical operation
    \$service = \$this->app->make(\$this->getServiceClass('{$serviceName}'));
    \$result = \$service->processCritical(\$data);
    
    // Assert: Audit log should be created via AuditService
    \$auditLog = \$this->auditService->findByActionAndEntityId(
        '{$verticalLower}_critical_processed',
        \$result['id']
    );
    
    expect(\$auditLog)->not->BeNull();
    
    // Assert audit log contains no PII
    \$this->assertAuditLogsContainNoPii([[
        'action' => \$auditLog->action,
        'entity_id' => \$auditLog->entity_id,
        'user_id' => \$auditLog->user_id,
    ]]);
});

test('{$vertical} concurrent operations respect quota limits', function () {
    // Arrange
    \$userId = 1;
    \$this->cacheService->put("quota:user:{$userId}:limit", 5);
    \$this->cacheService->put("quota:user:{$userId}:used", 0);
    
    \$results = \Tests\Helpers\ConcurrencyTestHelper::testQuotaIncrementRaceCondition(
        \$userId,
        5, // limit
        10 // concurrent attempts
    );
    
    // Assert: Only 5 should succeed
    expect(\$results['final_count'])->toBeLessThanOrEqual(5);
    expect(\$results['race_condition_detected'])->toBeFalse();
});

test('{$vertical} operation with valid quota and no fraud proceeds successfully', function () {
    // Arrange: Set quota to available
    \$userId = 1;
    \$this->cacheService->put("quota:user:{$userId}:limit", 10);
    \$this->cacheService->put("quota:user:{$userId}:used", 2);
    
    \$validData = array_merge(
        \$this->createVerticalTestData(['user_id' => \$userId]),
        FraudTestHelper::createLegitimateTransaction(['user_id' => \$userId])
    );
    
    FraudTestHelper::mockFraudMLPrediction(0.05); // Low fraud score
    
    // Act: Process operation
    \$service = \$this->app->make(\$this->getServiceClass('{$serviceName}'));
    \$quotaCheck = \$service->checkQuota(\$userId);
    \$fraudCheck = \$service->checkFraud(\$validData);
    
    // Assert: Both checks should pass
    expect(\$quotaCheck['allowed'])->toBeTrue();
    expect(\$fraudCheck['is_fraud'])->toBeFalse();
    
    // Assert quota is incremented
    expect(\$this->cacheService->get("quota:user:{$userId}:used"))->toBe(3);
});

test('{$vertical} data is encrypted in storage', function () {
    // Arrange
    \$data = \$this->createVerticalTestData(['sensitive_field' => 'secret']);
    
    // Act: Store data
    \$service = \$this->app->make(\$this->getServiceClass('{$serviceName}'));
    \$stored = \$service->store(\$data);
    
    // Assert: Sensitive fields should be encrypted
    \$this->assertSensitiveFieldsEncrypted(\$stored, ['sensitive_field']);
});
PHP;
    }

    private function getCriticalPathTestTemplate(string $vertical): string
    {
        $verticalLower = Str::lower($vertical);
        $serviceName = "{$vertical}Service";

        return <<<PHP
<?php

declare(strict_types=1);

namespace Tests\Feature\\{$vertical};


// Pest test using modern declarative syntax
uses(BaseVerticalTestCase::class);

beforeEach(function () {
    \$this->setVerticalContext('{$vertical}');
    \$this->cacheService = \$this->app->make(CacheService::class);
    \$this->auditService = \$this->app->make(AuditService::class);
});

test('{$vertical} operation with quota exceeded is handled correctly', function () {
    // Arrange: Set quota to exceeded
    \$userId = 1;
    \$this->cacheService->put("quota:user:{$userId}:limit", 10);
    \$this->cacheService->put("quota:user:{$userId}:used", 10);
    
    \$data = \$this->createVerticalTestData(['user_id' => \$userId]);
    
    // Act: Process operation
    \$service = \$this->app->make(\$this->getServiceClass('{$serviceName}'));
    \$quotaCheck = \$service->checkQuota(\$userId);
    
    // Assert: Quota should be exceeded
    expect(\$quotaCheck['allowed'])->toBeFalse();
    expect(\$quotaCheck['reason'])->toBe('quota_exceeded');
});

test('{$vertical} operation with fraud block is handled correctly', function () {
    // Arrange: Create suspicious data
    \$suspiciousData = array_merge(
        \$this->createVerticalTestData(),
        FraudTestHelper::createSuspiciousTransaction()
    );
    
    FraudTestHelper::mockFraudMLPrediction(0.85); // High fraud score
    
    // Act: Check fraud
    \$service = \$this->app->make(\$this->getServiceClass('{$serviceName}'));
    \$fraudCheck = \$service->checkFraud(\$suspiciousData);
    
    // Assert: Operation should be blocked
    expect(\$fraudCheck['is_fraud'])->toBeTrue();
    expect(\$fraudCheck['action'])->toBe('block');
});

test('{$vertical} operation with quota exceeded and fraud block simultaneously', function () {
    // Arrange: Set quota to exceeded and create suspicious data
    \$userId = 1;
    \$this->cacheService->put("quota:user:{$userId}:limit", 10);
    \$this->cacheService->put("quota:user:{$userId}:used", 11);
    
    \$suspiciousData = array_merge(
        \$this->createVerticalTestData(['user_id' => \$userId]),
        FraudTestHelper::createSuspiciousTransaction()
    );
    
    FraudTestHelper::mockFraudMLPrediction(0.92);
    
    // Act: Process operation
    \$service = \$this->app->make(\$this->getServiceClass('{$serviceName}'));
    \$quotaCheck = \$service->checkQuota(\$userId);
    \$fraudCheck = \$service->checkFraud(\$suspiciousData);
    
    // Assert: Both checks should fail
    expect(\$quotaCheck['allowed'])->toBeFalse();
    expect(\$fraudCheck['is_fraud'])->toBeTrue();
});

test('{$vertical} critical operation creates proper audit trail', function () {
    // Arrange
    \$data = \$this->createVerticalTestData();
    
    // Act: Process critical operation
    \$service = \$this->app->make(\$this->getServiceClass('{$serviceName}'));
    \$result = \$service->processCritical(\$data);
    
    // Assert: Audit log should be created via AuditService
    \$auditLog = \$this->auditService->findByActionAndEntityId(
        '{$verticalLower}_critical_processed',
        \$result['id']
    );
    
    expect(\$auditLog)->not->BeNull();
    
    // Assert audit log contains no PII
    \$this->assertAuditLogsContainNoPii([[
        'action' => \$auditLog->action,
        'entity_id' => \$auditLog->entity_id,
        'user_id' => \$auditLog->user_id,
    ]]);
});

test('{$vertical} concurrent operations respect quota limits', function () {
    // Arrange
    \$userId = 1;
    \$this->cacheService->put("quota:user:{$userId}:limit", 5);
    \$this->cacheService->put("quota:user:{$userId}:used", 0);
    
    \$results = \Tests\Helpers\ConcurrencyTestHelper::testQuotaIncrementRaceCondition(
        \$userId,
        5, // limit
        10 // concurrent attempts
    );
    
    // Assert: Only 5 should succeed
    expect(\$results['final_count'])->toBeLessThanOrEqual(5);
    expect(\$results['race_condition_detected'])->toBeFalse();
});

test('{$vertical} operation with valid quota and no fraud proceeds successfully', function () {
    // Arrange: Set quota to available
    \$userId = 1;
    \$this->cacheService->put("quota:user:{$userId}:limit", 10);
    \$this->cacheService->put("quota:user:{$userId}:used", 2);
    
    \$validData = array_merge(
        \$this->createVerticalTestData(['user_id' => \$userId]),
        FraudTestHelper::createLegitimateTransaction(['user_id' => \$userId])
    );
    
    FraudTestHelper::mockFraudMLPrediction(0.05); // Low fraud score
    
    // Act: Process operation
    \$service = \$this->app->make(\$this->getServiceClass('{$serviceName}'));
    \$quotaCheck = \$service->checkQuota(\$userId);
    \$fraudCheck = \$service->checkFraud(\$validData);
    
    // Assert: Both checks should pass
    expect(\$quotaCheck['allowed'])->toBeTrue();
    expect(\$fraudCheck['is_fraud'])->toBeFalse();
    
    // Assert quota is incremented
    expect(\$this->cacheService->get("quota:user:{$userId}:used"))->toBe(3);
});

test('{$vertical} data is encrypted in storage', function () {
    // Arrange
    \$data = \$this->createVerticalTestData(['sensitive_field' => 'secret']);
    
    // Act: Store data
    \$service = \$this->app->make(\$this->getServiceClass('{$serviceName}'));
    \$stored = \$service->store(\$data);
    
    // Assert: Sensitive fields should be encrypted
    \$this->assertSensitiveFieldsEncrypted(\$stored, ['sensitive_field']);
});
PHP;
    }
}
