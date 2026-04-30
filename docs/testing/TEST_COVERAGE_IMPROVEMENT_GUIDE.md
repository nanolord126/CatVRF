# Test Coverage Improvement Guide
## CatVRF 2026 - Section 12 Implementation

**Date:** 25.04.2026  
**Estimated Time:** 40-60 hours  
**Status:** 🔄 IN PROGRESS

---

## Overview

This guide provides detailed steps for improving test coverage across the CatVRF codebase to achieve ≥98.5% coverage for unit, feature, and integration tests.

---

## Current State Analysis

### 1.1 Coverage Audit Script

**File:** `scripts/analyze-coverage.php`

```php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Finder\Finder;

$directories = [
    'app/Services' => 'Services',
    'modules' => 'Modules',
    'app/Models' => 'Models',
    'routes' => 'Routes',
];

$totalFiles = 0;
$testedFiles = 0;
$coverageReport = [];

foreach ($directories as $directory => $category) {
    $finder = new Finder();
    $finder->files()
        ->in(__DIR__ . '/../' . $directory)
        ->name('*.php')
        ->exclude(['Archived', 'Stubs']);

    $files = iterator_to_array($finder);
    $categoryTotal = count($files);
    $categoryTested = 0;
    $untestedFiles = [];

    foreach ($files as $file) {
        $totalFiles++;
        $relativePath = $file->getRelativePathname();
        $testPath = __DIR__ . '/../tests/' . $category . '/' . str_replace('.php', 'Test.php', $relativePath);
        
        if (file_exists($testPath)) {
            $categoryTested++;
            $testedFiles++;
        } else {
            $untestedFiles[] = $relativePath;
        }
    }

    $coverageReport[$category] = [
        'total' => $categoryTotal,
        'tested' => $categoryTested,
        'coverage' => $categoryTotal > 0 ? round(($categoryTested / $categoryTotal) * 100, 2) : 0,
        'untested' => $untestedFiles,
    ];
}

// Generate report
echo "Test Coverage Analysis\n";
echo str_repeat('=', 50) . "\n\n";

foreach ($coverageReport as $category => $data) {
    echo "{$category}:\n";
    echo "  Total Files: {$data['total']}\n";
    echo "  Tested Files: {$data['tested']}\n";
    echo "  Coverage: {$data['coverage']}%\n";
    
    if (!empty($data['untested'])) {
        echo "  Untested Files:\n";
        foreach (array_slice($data['untested'], 0, 10) as $file) {
            echo "    - {$file}\n";
        }
        if (count($data['untested']) > 10) {
            echo "    ... and " . (count($data['untested']) - 10) . " more\n";
        }
    }
    echo "\n";
}

$overallCoverage = $totalFiles > 0 ? round(($testedFiles / $totalFiles) * 100, 2) : 0;
echo str_repeat('=', 50) . "\n";
echo "Overall Coverage: {$overallCoverage}%\n";
echo "Total Files: {$totalFiles}\n";
echo "Tested Files: {$testedFiles}\n";
```

---

## Phase 1: Service Layer Tests (20-30 hours)

### 1.1 Service Test Generator

**File:** `scripts/generate-service-test.php`

```php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Finder\Finder;

$serviceFile = $argv[1] ?? null;

if (!$serviceFile) {
    echo "Usage: php generate-service-test.php <service-file-path>\n";
    exit(1);
}

if (!file_exists($serviceFile)) {
    echo "Error: File not found: {$serviceFile}\n";
    exit(1);
}

$content = file_get_contents($serviceFile);

// Extract class name
preg_match('/class\s+(\w+)/', $content, $matches);
$className = $matches[1] ?? 'Service';

// Extract namespace
preg_match('/namespace\s+([\w\\\\]+);/', $content, $matches);
$namespace = $matches[1] ?? '';

// Extract constructor dependencies
preg_match('/public function __construct\((.*?)\)/s', $content, $matches);
$constructorParams = $matches[1] ?? '';

// Parse dependencies
$dependencies = [];
preg_match_all('/(private|public|protected)\s+(readonly\s+)?(\w+)\s+\$(\w+)/', $constructorParams, $depMatches);
foreach ($depMatches[3] as $index => $type) {
    $dependencies[] = [
        'type' => $type,
        'name' => $depMatches[4][$index],
    ];
}

// Generate test class
$testNamespace = str_replace(['app\\', 'Modules\\'], ['Tests\\Unit\\', 'Tests\\Unit\\Modules\\'], $namespace);
$testClassName = $className . 'Test';

$testContent = "<?php\n\ndeclare(strict_types=1);\n\nnamespace {$testNamespace};\n\nuse Tests\\Unit\\Services\\ServiceTestCase;\nuse {$namespace}\\{$className};\n";
foreach ($dependencies as $dep) {
    $testContent .= "use {$dep['type']};\n";
}
$testContent .= "\nfinal class {$testClassName} extends ServiceTestCase\n{\n";
$testContent .= "    private {$className} \${$className};\n";
foreach ($dependencies as $dep) {
    $testContent .= "    private {$dep['type']} \${$dep['name']};\n";
}
$testContent .= "\n    protected function setUp(): void\n    {\n";
$testContent .= "        parent::setUp();\n\n";
foreach ($dependencies as $dep) {
    $testContent .= "        \$this->{$dep['name']} = \$this->mockExternalService({$dep['type']}::class);\n";
}
$testContent .= "\n        \$this->{$className} = new {$className}(\n";
foreach ($dependencies as $index => $dep) {
    $testContent .= "            \$this->{$dep['name']}" . ($index < count($dependencies) - 1 ? ",\n" : "\n");
}
$testContent .= "        );\n";
$testContent .= "    }\n\n";
$testContent .= "    public function test_instantiation(): void\n";
$testContent .= "    {\n";
$testContent .= "        \$this->assertInstanceOf({$className}::class, \$this->{$className});\n";
$testContent .= "    }\n";
$testContent .= "}\n";

// Determine test file path
$relativePath = str_replace([__DIR__ . '/../app/', __DIR__ . '/../modules/'], '', $serviceFile);
$testPath = __DIR__ . '/../tests/Unit/' . $relativePath;
$testPath = str_replace('.php', 'Test.php', $testPath);

// Create directory if not exists
$testDir = dirname($testPath);
if (!is_dir($testDir)) {
    mkdir($testDir, 0755, true);
}

// Write test file
file_put_contents($testPath, $testContent);

echo "Test file generated: {$testPath}\n";
echo "Please add actual test methods to the generated test class.\n";
```

### 1.2 Critical Services Test Coverage Priority

**High Priority Services (Must have 100% coverage):**

1. **Payment Services**
   - `modules/Payment/Application/Services/PaymentService.php`
   - `modules/Payment/Services/PaymentGatewayService.php`
   - `modules/Payment/Services/PaymentFraudService.php`

2. **Security Services**
   - `app/Services/Security/FraudMLService.php`
   - `app/Services/Security/RiskScoreEngine.php`
   - `app/Services/Security/InsiderThreatService.php`
   - `app/Services/Security/VoiceBiometricsService.php`

3. **Wallet Services**
   - `modules/Wallet/Services/WalletService.php`
   - `modules/Wallet/Services/WalletTransactionService.php`

4. **Audit Service**
   - `app/Services/Audit/AuditService.php`

**Medium Priority Services:**

5. **Vertical Services**
   - `app/Services/VeterinaryService.php`
   - `app/Services/UserProfileService.php`
   - All vertical-specific services

### 1.3 Example: PaymentService Test

**File:** `tests/Unit/Payment/PaymentServiceTest.php`

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Payment;

use Tests\Unit\Services\ServiceTestCase;
use Modules\Payment\Services\PaymentService;
use Modules\Payment\DTO\PaymentDTO;
use Modules\Payment\Models\Payment;
use App\Services\Security\FraudMLService;
use App\Services\Audit\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Payment\Events\PaymentCompleted;
use Modules\Payment\Events\PaymentFailed;
use Modules\Payment\Exceptions\InsufficientFundsException;
use Modules\Payment\Exceptions\FraudDetectedException;

final class PaymentServiceTest extends ServiceTestCase
{
    private PaymentService $paymentService;
    private FraudMLService $fraudMLService;
    private AuditService $auditService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fraudMLService = $this->mockExternalService(FraudMLService::class);
        $this->auditService = $this->mockExternalService(AuditService::class);
        
        $this->paymentService = new PaymentService(
            $this->fraudMLService,
            $this->auditService,
            app(\Illuminate\Log\LogManager::class),
            app(\Illuminate\Database\DatabaseManager::class),
            app(\Illuminate\Cache\CacheManager::class),
        );

        Event::fake();
    }

    public function test_create_payment_successfully(): void
    {
        // Arrange
        $dto = new PaymentDTO(
            userId: 1,
            amount: 10000,
            currency: 'RUB',
            paymentMethod: 'card',
            metadata: ['order_id' => 123]
        );

        $this->fraudMLService
            ->shouldReceive('checkTransaction')
            ->once()
            ->andReturn(['is_fraud' => false, 'score' => 0.1]);

        $this->auditService
            ->shouldReceive('logCreated')
            ->once()
            ->with('Payment', \Mockery::type('int'), \Mockery::type('array'));

        // Act
        $payment = $this->paymentService->create($dto);

        // Assert
        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertEquals(10000, $payment->amount);
        $this->assertEquals('pending', $payment->status);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'user_id' => 1,
            'amount' => 10000,
        ]);
    }

    public function test_create_payment_fails_on_fraud_detection(): void
    {
        // Arrange
        $dto = new PaymentDTO(
            userId: 1,
            amount: 10000,
            currency: 'RUB',
            paymentMethod: 'card',
            metadata: ['order_id' => 123]
        );

        $this->fraudMLService
            ->shouldReceive('checkTransaction')
            ->once()
            ->andReturn(['is_fraud' => true, 'score' => 0.95]);

        // Act & Assert
        $this->expectException(FraudDetectedException::class);
        $this->paymentService->create($dto);
    }

    public function test_process_payment_successfully(): void
    {
        // Arrange
        $payment = Payment::factory()->create([
            'status' => 'pending',
            'amount' => 10000,
        ]);

        $this->fraudMLService
            ->shouldReceive('checkTransaction')
            ->once()
            ->andReturn(['is_fraud' => false, 'score' => 0.1]);

        $this->auditService
            ->shouldReceive('logAction')
            ->once()
            ->with('payment_processed', 'Payment', $payment->id, \Mockery::type('array'));

        // Act
        $result = $this->paymentService->process($payment->id);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'completed',
        ]);
        Event::assertDispatched(PaymentCompleted::class);
    }

    public function test_process_payment_fails_on_gateway_error(): void
    {
        // Arrange
        $payment = Payment::factory()->create([
            'status' => 'pending',
            'amount' => 10000,
        ]);

        $this->fraudMLService
            ->shouldReceive('checkTransaction')
            ->once()
            ->andThrow(new \Exception('Gateway error'));

        $this->auditService
            ->shouldReceive('logAction')
            ->once()
            ->with('payment_failed', 'Payment', $payment->id, \Mockery::type('array'));

        // Act
        $result = $this->paymentService->process($payment->id);

        // Assert
        $this->assertFalse($result);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'failed',
        ]);
        Event::assertDispatched(PaymentFailed::class);
    }

    public function test_refund_payment_successfully(): void
    {
        // Arrange
        $payment = Payment::factory()->create([
            'status' => 'completed',
            'amount' => 10000,
        ]);

        $this->auditService
            ->shouldReceive('logAction')
            ->once()
            ->with('payment_refunded', 'Payment', $payment->id, \Mockery::type('array'));

        // Act
        $result = $this->paymentService->refund($payment->id);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'refunded',
        ]);
    }

    public function test_get_payment_by_id(): void
    {
        // Arrange
        $payment = Payment::factory()->create();

        // Act
        $result = $this->paymentService->getById($payment->id);

        // Assert
        $this->assertInstanceOf(Payment::class, $result);
        $this->assertEquals($payment->id, $result->id);
    }

    public function test_get_payments_by_user(): void
    {
        // Arrange
        $user = $this->createTestUser();
        Payment::factory()->count(5)->create(['user_id' => $user->id]);
        Payment::factory()->count(3)->create(['user_id' => $user->id + 1]);

        // Act
        $payments = $this->paymentService->getByUser($user->id);

        // Assert
        $this->assertCount(5, $payments);
        foreach ($payments as $payment) {
            $this->assertEquals($user->id, $payment->user_id);
        }
    }

    public function test_calculate_payment_fees(): void
    {
        // Arrange
        $amount = 10000;
        $paymentMethod = 'card';

        // Act
        $fees = $this->paymentService->calculateFees($amount, $paymentMethod);

        // Assert
        $this->assertIsArray($fees);
        $this->assertArrayHasKey('processing_fee', $fees);
        $this->assertArrayHasKey('total_fee', $fees);
        $this->assertGreaterThan(0, $fees['total_fee']);
    }
}
```

### 1.4 Example: FraudMLService Test

**File:** `tests/Unit/Security/FraudMLServiceTest.php`

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Security;

use Tests\Unit\Services\ServiceTestCase;
use App\Services\Security\FraudMLService;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

final class FraudMLServiceTest extends ServiceTestCase
{
    private FraudMLService $fraudMLService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fraudMLService = app(FraudMLService::class);
    }

    public function test_check_transaction_legitimate(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'created_at' => now()->subDays(30),
        ]);

        $transaction = [
            'user_id' => $user->id,
            'amount' => 1000,
            'currency' => 'RUB',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
        ];

        // Act
        $result = $this->fraudMLService->checkTransaction($transaction);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('is_fraud', $result);
        $this->assertArrayHasKey('score', $result);
        $this->assertFalse($result['is_fraud']);
        $this->assertLessThan(0.5, $result['score']);
    }

    public function test_check_transaction_suspicious_high_amount(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'created_at' => now()->subDays(30),
        ]);

        $transaction = [
            'user_id' => $user->id,
            'amount' => 1000000, // Very high amount
            'currency' => 'RUB',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
        ];

        // Act
        $result = $this->fraudMLService->checkTransaction($transaction);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('is_fraud', $result);
        $this->assertArrayHasKey('score', $result);
        $this->assertGreaterThan(0.5, $result['score']);
    }

    public function test_check_transaction_new_user_high_risk(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'created_at' => now()->subMinutes(5), // Very new user
        ]);

        $transaction = [
            'user_id' => $user->id,
            'amount' => 50000,
            'currency' => 'RUB',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
        ];

        // Act
        $result = $this->fraudMLService->checkTransaction($transaction);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('is_fraud', $result);
        $this->assertArrayHasKey('score', $result);
        $this->assertGreaterThan(0.6, $result['score']);
    }

    public function test_check_transaction_suspicious_ip(): void
    {
        // Arrange
        $user = User::factory()->create();

        $transaction = [
            'user_id' => $user->id,
            'amount' => 10000,
            'currency' => 'RUB',
            'ip_address' => '10.0.0.0', // Suspicious IP range
            'user_agent' => 'Mozilla/5.0',
        ];

        // Act
        $result = $this->fraudMLService->checkTransaction($transaction);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('is_fraud', $result);
        $this->assertArrayHasKey('score', $result);
        $this->assertGreaterThan(0.7, $result['score']);
    }

    public function test_cache_fraud_check_results(): void
    {
        // Arrange
        $user = User::factory()->create();
        $transaction = [
            'user_id' => $user->id,
            'amount' => 1000,
            'currency' => 'RUB',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
        ];

        $cacheKey = "fraud_check:{$user->id}:" . md5(json_encode($transaction));

        // Act
        $this->fraudMLService->checkTransaction($transaction);
        
        // Assert
        $this->assertNotNull(Cache::get($cacheKey));
    }

    public function test_get_user_risk_score(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'created_at' => now()->subDays(30),
        ]);

        // Act
        $riskScore = $this->fraudMLService->getUserRiskScore($user->id);

        // Assert
        $this->assertIsFloat($riskScore);
        $this->assertGreaterThanOrEqual(0, $riskScore);
        $this->assertLessThanOrEqual(1, $riskScore);
    }
}
```

---

## Phase 2: Model Tests (8-12 hours)

### 2.1 Model Test Template

**File:** `tests/Unit/Models/ModelTestCase.php`

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use Tests\BaseTestCase;
use Illuminate\Database\Eloquent\Model;

abstract class ModelTestCase extends BaseTestCase
{
    abstract protected function getModelClass(): string;

    protected function assertModelExists(Model $model): void
    {
        $this->assertDatabaseHas($model->getTable(), [$model->getKeyName() => $model->getKey()]);
    }

    protected function assertModelMissing(Model $model): void
    {
        $this->assertDatabaseMissing($model->getTable(), [$model->getKeyName() => $model->getKey()]);
    }

    protected function assertHasCast(Model $model, string $attribute, string $type): void
    {
        $casts = $model->getCasts();
        $this->assertArrayHasKey($attribute, $casts);
        $this->assertEquals($type, $casts[$attribute]);
    }

    protected function assertHasFillable(Model $model, string $attribute): void
    {
        $this->assertContains($attribute, $model->getFillable());
    }

    protected function assertHasHidden(Model $model, string $attribute): void
    {
        $this->assertContains($attribute, $model->getHidden());
    }
}
```

### 2.2 Example: Payment Model Test

**File:** `tests/Unit/Models/PaymentTest.php`

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use Modules\Payment\Models\Payment;
use Modules\Payment\Enums\PaymentStatus;
use App\Models\User;

final class PaymentTest extends ModelTestCase
{
    protected function getModelClass(): string
    {
        return Payment::class;
    }

    public function test_payment_belongs_to_user(): void
    {
        // Arrange
        $user = User::factory()->create();
        $payment = Payment::factory()->create(['user_id' => $user->id]);

        // Act
        $paymentUser = $payment->user;

        // Assert
        $this->assertInstanceOf(User::class, $paymentUser);
        $this->assertEquals($user->id, $paymentUser->id);
    }

    public function test_payment_has_status_enum(): void
    {
        // Arrange
        $payment = Payment::factory()->create(['status' => PaymentStatus::PENDING]);

        // Assert
        $this->assertEquals(PaymentStatus::PENDING, $payment->status);
    }

    public function test_payment_amount_is_cast_to_decimal(): void
    {
        // Arrange
        $payment = Payment::factory()->create(['amount' => '100.50']);

        // Assert
        $this->assertIsFloat($payment->amount);
        $this->assertEquals(100.50, $payment->amount);
    }

    public function test_payment_metadata_is_cast_to_json(): void
    {
        // Arrange
        $metadata = ['order_id' => 123, 'product_id' => 456];
        $payment = Payment::factory()->create(['metadata' => $metadata]);

        // Assert
        $this->assertIsArray($payment->metadata);
        $this->assertEquals($metadata, $payment->metadata);
    }

    public function test_payment_can_be_scoped_by_status(): void
    {
        // Arrange
        Payment::factory()->count(3)->create(['status' => PaymentStatus::PENDING]);
        Payment::factory()->count(2)->create(['status' => PaymentStatus::COMPLETED]);
        Payment::factory()->count(1)->create(['status' => PaymentStatus::FAILED]);

        // Act
        $pendingPayments = Payment::byStatus(PaymentStatus::PENDING)->get();
        $completedPayments = Payment::byStatus(PaymentStatus::COMPLETED)->get();

        // Assert
        $this->assertCount(3, $pendingPayments);
        $this->assertCount(2, $completedPayments);
    }

    public function test_payment_can_be_scoped_by_user(): void
    {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        Payment::factory()->count(3)->create(['user_id' => $user1->id]);
        Payment::factory()->count(2)->create(['user_id' => $user2->id]);

        // Act
        $user1Payments = Payment::byUser($user1->id)->get();
        $user2Payments = Payment::byUser($user2->id)->get();

        // Assert
        $this->assertCount(3, $user1Payments);
        $this->assertCount(2, $user2Payments);
    }

    public function test_payment_is_pending_when_status_is_pending(): void
    {
        // Arrange
        $payment = Payment::factory()->create(['status' => PaymentStatus::PENDING]);

        // Assert
        $this->assertTrue($payment->isPending());
        $this->assertFalse($payment->isCompleted());
        $this->assertFalse($payment->isFailed());
    }

    public function test_payment_is_completed_when_status_is_completed(): void
    {
        // Arrange
        $payment = Payment::factory()->create(['status' => PaymentStatus::COMPLETED]);

        // Assert
        $this->assertFalse($payment->isPending());
        $this->assertTrue($payment->isCompleted());
        $this->assertFalse($payment->isFailed());
    }

    public function test_payment_is_failed_when_status_is_failed(): void
    {
        // Arrange
        $payment = Payment::factory()->create(['status' => PaymentStatus::FAILED]);

        // Assert
        $this->assertFalse($payment->isPending());
        $this->assertFalse($payment->isCompleted());
        $this->assertTrue($payment->isFailed());
    }
}
```

---

## Phase 3: API Endpoint Tests (8-12 hours)

### 3.1 API Test Coverage Checklist

**Critical Endpoints (100% coverage required):**

1. **Authentication**
   - POST /api/v1/auth/register
   - POST /api/v1/auth/login
   - POST /api/v1/auth/logout
   - POST /api/v1/auth/refresh
   - POST /api/v1/auth/password/reset
   - POST /api/v1/auth/password/confirm

2. **Payments**
   - GET /api/v1/payments
   - POST /api/v1/payments
   - GET /api/v1/payments/{id}
   - PUT /api/v1/payments/{id}
   - DELETE /api/v1/payments/{id}
   - POST /api/v1/payments/{id}/process
   - POST /api/v1/payments/{id}/refund

3. **Users**
   - GET /api/v1/users/me
   - PUT /api/v1/users/me
   - GET /api/v1/users/{id}

4. **Wallet**
   - GET /api/v1/wallet
   - POST /api/v1/wallet/deposit
   - POST /api/v1/wallet/withdraw
   - GET /api/v1/wallet/transactions

### 3.2 Example: Payment API Test

**File:** `tests/Feature/Payment/PaymentApiTest.php`

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use Tests\Feature\Api\ApiTestCase;
use Modules\Payment\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Modules\Payment\Events\PaymentCompleted;

final class PaymentApiTest extends ApiTestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_create_payment_via_api(): void
    {
        // Arrange
        $data = [
            'amount' => 10000,
            'currency' => 'RUB',
            'payment_method' => 'card',
            'metadata' => ['order_id' => 123],
        ];

        // Act
        $response = $this->apiPost('/payments', $data);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'amount',
                    'currency',
                    'status',
                    'created_at',
                ],
            ]);

        $this->assertDatabaseHas('payments', [
            'user_id' => $this->user->id,
            'amount' => 10000,
            'status' => 'pending',
        ]);
    }

    public function test_create_payment_validation_fails_without_amount(): void
    {
        // Arrange
        $data = [
            'currency' => 'RUB',
            'payment_method' => 'card',
        ];

        // Act
        $response = $this->apiPost('/payments', $data);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_get_payment_via_api(): void
    {
        // Arrange
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
        ]);

        // Act
        $response = $this->apiGet("/payments/{$payment->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'amount',
                    'currency',
                    'status',
                    'created_at',
                ],
            ])
            ->assertJson([
                'data' => [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                ],
            ]);
    }

    public function test_list_payments_via_api(): void
    {
        // Arrange
        Payment::factory()->count(10)->create([
            'user_id' => $this->user->id,
        ]);

        // Act
        $response = $this->apiGet('/payments');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'amount',
                        'currency',
                        'status',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                ],
            ]);
    }

    public function test_list_payments_with_pagination(): void
    {
        // Arrange
        Payment::factory()->count(25)->create([
            'user_id' => $this->user->id,
        ]);

        // Act
        $response = $this->apiGet('/payments?page=2&per_page=10');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'meta' => [
                    'current_page' => 2,
                    'per_page' => 10,
                ],
            ]);
    }

    public function test_unauthorized_access_to_payments(): void
    {
        // Arrange
        $otherUser = User::factory()->create();
        $payment = Payment::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        // Act
        $response = $this->apiGet("/payments/{$payment->id}");

        // Assert
        $response->assertStatus(403);
    }

    public function test_process_payment_via_api(): void
    {
        // Arrange
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        Event::fake();

        // Act
        $response = $this->apiPost("/payments/{$payment->id}/process");

        // Assert
        $response->assertStatus(200);
        
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'completed',
        ]);
    }

    public function test_refund_payment_via_api(): void
    {
        // Arrange
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'completed',
            'amount' => 10000,
        ]);

        // Act
        $response = $this->apiPost("/payments/{$payment->id}/refund");

        // Assert
        $response->assertStatus(200);
        
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'refunded',
        ]);
    }
}
```

---

## Phase 4: Integration Tests (4-8 hours)

### 4.1 Integration Test Examples

**File:** `tests/Integration/PaymentWalletIntegrationTest.php`

```php
<?php

declare(strict_types=1);

namespace Tests\Integration;

use Modules\Payment\Services\PaymentService;
use Modules\Wallet\Services\WalletService;
use Modules\Payment\Models\Payment;
use Modules\Wallet\Models\Wallet;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Modules\Payment\Jobs\ProcessPaymentJob;
use Modules\Wallet\Jobs\CreditWalletJob;

final class PaymentWalletIntegrationTest extends IntegrationTestCase
{
    private PaymentService $paymentService;
    private WalletService $walletService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentService = app(PaymentService::class);
        $this->walletService = app(WalletService::class);
    }

    public function test_payment_completion_credits_wallet(): void
    {
        // Arrange
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 0,
        ]);

        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'amount' => 10000,
            'status' => 'pending',
        ]);

        // Act
        $this->paymentService->complete($payment->id);

        // Assert
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'balance' => 10000,
        ]);

        Queue::assertPushed(CreditWalletJob::class);
    }

    public function test_payment_failure_does_not_credit_wallet(): void
    {
        // Arrange
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 0,
        ]);

        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'amount' => 10000,
            'status' => 'pending',
        ]);

        // Act
        $this->paymentService->fail($payment->id, 'Gateway error');

        // Assert
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'failed',
        ]);

        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'balance' => 0,
        ]);

        Queue::assertNotPushed(CreditWalletJob::class);
    }

    public function test_wallet_withdrawal_creates_payment(): void
    {
        // Arrange
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 10000,
        ]);

        // Act
        $this->walletService->withdraw($wallet->id, 5000, 'Test withdrawal');

        // Assert
        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'balance' => 5000,
        ]);

        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $wallet->id,
            'amount' => -5000,
            'type' => 'withdrawal',
        ]);
    }
}
```

---

## Phase 5: Coverage Reporting and Monitoring (4-6 hours)

### 5.1 Generate Coverage Report

**Script:** `scripts/generate-coverage-report.sh`

```bash
#!/bin/bash

echo "Generating Test Coverage Report..."

# Run tests with coverage
vendor/bin/pest --coverage --coverage-html=coverage/html --coverage-clover=coverage/clover.xml

# Generate summary
echo "Coverage Summary:"
echo "=================="

# Extract coverage percentage from clover.xml
if command -v php &> /dev/null; then
    php -r "
    \$xml = simplexml_load_file('coverage/clover.xml');
    \$metrics = \$xml->project->metrics;
    \$elements = (int)\$metrics['elements'];
    \$coveredelements = (int)\$metrics['coveredelements'];
    \$coverage = \$elements > 0 ? round((\$coveredelements / \$elements) * 100, 2) : 0;
    echo \"Overall Coverage: {\$coverage}%\n\";
    echo \"Elements: {\$elements}\n\";
    echo \"Covered Elements: {\$coveredelements}\n\";
    "
fi

echo ""
echo "HTML report generated at: coverage/html/index.html"
echo "Clover XML report generated at: coverage/clover.xml"

# Check if coverage meets threshold (98.5%)
THRESHOLD=98.5
COVERAGE=$(php -r "
\$xml = simplexml_load_file('coverage/clover.xml');
\$metrics = \$xml->project->metrics;
\$elements = (int)\$metrics['elements'];
\$coveredelements = (int)\$metrics['coveredelements'];
echo \$elements > 0 ? round((\$coveredelements / \$elements) * 100, 2) : 0;
")

if (( $(echo "$COVERAGE < $THRESHOLD" | bc -l) )); then
    echo "WARNING: Coverage ($COVERAGE%) is below threshold ($THRESHOLD%)"
    exit 1
else
    echo "SUCCESS: Coverage ($COVERAGE%) meets threshold ($THRESHOLD%)"
    exit 0
fi
```

### 5.2 Coverage Badge Generation

**Script:** `scripts/generate-coverage-badge.sh`

```bash
#!/bin/bash

COVERAGE=$(php -r "
\$xml = simplexml_load_file('coverage/clover.xml');
\$metrics = \$xml->project->metrics;
\$elements = (int)\$metrics['elements'];
\$coveredelements = (int)\$metrics['coveredelements'];
echo \$elements > 0 ? round((\$coveredelements / \$elements) * 100, 2) : 0;
")

COLOR="#4c1" # brightgreen
if (( $(echo "$COVERAGE < 90" | bc -l) )); then
    COLOR="#dfb317" # yellow
fi
if (( $(echo "$COVERAGE < 70" | bc -l) )); then
    COLOR="#e05d44" # red
fi

# Generate SVG badge
cat > coverage/coverage-badge.svg << EOF
<svg xmlns="http://www.w3.org/2000/svg" width="100" height="20">
  <linearGradient id="b" x2="0" y2="100%">
    <stop offset="0" stop-color="#bbb" stop-opacity=".1"/>
    <stop offset="1" stop-opacity=".1"/>
  </linearGradient>
  <mask id="a">
    <rect width="100" height="20" rx="3" fill="#fff"/>
  </mask>
  <g mask="url(#a)">
    <path fill="#555" d="M0 0h55v20H0z"/>
    <path fill="$COLOR" d="M55 0h45v20H55z"/>
    <path fill="url(#b)" d="M0 0h100v20H0z"/>
  </g>
  <g fill="#fff" text-anchor="middle" font-family="DejaVu Sans,Verdana,Geneva,sans-serif" font-size="11">
    <text x="27.5" y="15" fill="#010101" fill-opacity=".3">coverage</text>
    <text x="27.5" y="14">coverage</text>
    <text x="77.5" y="15" fill="#010101" fill-opacity=".3">$COVERAGE%</text>
    <text x="77.5" y="14">$COVERAGE%</text>
  </g>
</svg>
EOF

echo "Coverage badge generated at: coverage/coverage-badge.svg"
```

---

## Success Criteria

- [ ] Unit test coverage ≥98.5%
- [ ] Feature test coverage ≥98.5%
- [ ] Integration test coverage ≥98.5%
- [ ] All critical services have 100% coverage
- [ ] All critical API endpoints have 100% coverage
- [ ] All models have comprehensive tests
- [ ] Coverage report generated automatically
- [ ] CI/CD pipeline enforces coverage threshold
- [ ] Coverage badge displayed in README

---

## Next Steps

1. Execute Phase 1: Service Layer Tests (20-30 hours)
2. Execute Phase 2: Model Tests (8-12 hours)
3. Execute Phase 3: API Endpoint Tests (8-12 hours)
4. Execute Phase 4: Integration Tests (4-8 hours)
5. Execute Phase 5: Coverage Reporting and Monitoring (4-6 hours)
6. Update COMPREHENSIVE_FIX_ROADMAP_2026.md with completion status

---

**Document Status:** 🔄 IN PROGRESS  
**Last Updated:** 25.04.2026  
**Next Review:** After Phase 1 completion
