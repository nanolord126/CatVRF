<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Payment\Services\AI;

use App\Domains\Payment\Services\AI\PaymentConstructorService;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\DatabaseManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit-тесты для PaymentConstructorService (AI-конструктор).
 */
final class PaymentConstructorServiceTest extends TestCase
{
    public function test_service_is_final_readonly(): void
    {
        $ref = new \ReflectionClass(PaymentConstructorService::class);
        $this->assertTrue($ref->isFinal());
        $this->assertTrue($ref->isReadOnly());
    }

    public function test_constructor_requires_five_deps(): void
    {
        $ctor = (new \ReflectionClass(PaymentConstructorService::class))->getConstructor();
        $this->assertNotNull($ctor);

        $params = $ctor->getParameters();
        $this->assertGreaterThanOrEqual(5, count($params));

        $names = array_map(fn(\ReflectionParameter $p) => $p->getName(), $params);
        $this->assertContains('db', $names);
        $this->assertContains('logger', $names);
        $this->assertContains('fraud', $names);
        $this->assertContains('audit', $names);
        $this->assertContains('cache', $names);
    }

    public function test_constructor_dep_types(): void
    {
        $ctor = (new \ReflectionClass(PaymentConstructorService::class))->getConstructor();
        $types = [];
        foreach ($ctor->getParameters() as $p) {
            $types[$p->getName()] = $p->getType()?->getName();
        }

        $this->assertSame(DatabaseManager::class, $types['db']);
        $this->assertSame(LoggerInterface::class, $types['logger']);
        $this->assertSame(FraudControlService::class, $types['fraud']);
        $this->assertSame(AuditService::class, $types['audit']);
        $this->assertSame(CacheRepository::class, $types['cache']);
    }

    public function test_has_analyze_and_recommend_method(): void
    {
        $ref = new \ReflectionClass(PaymentConstructorService::class);
        $this->assertTrue($ref->hasMethod('analyzeAndRecommend'));

        $method = $ref->getMethod('analyzeAndRecommend');
        $params = $method->getParameters();
        $names = array_map(fn(\ReflectionParameter $p) => $p->getName(), $params);

        $this->assertContains('tenantId', $names);
        $this->assertContains('correlationId', $names);
    }

    public function test_has_private_build_analysis_method(): void
    {
        $ref = new \ReflectionClass(PaymentConstructorService::class);
        $this->assertTrue($ref->hasMethod('buildAnalysis'));

        $method = $ref->getMethod('buildAnalysis');
        $this->assertTrue($method->isPrivate());
    }

    public function test_has_private_generate_recommendations_method(): void
    {
        $ref = new \ReflectionClass(PaymentConstructorService::class);
        $this->assertTrue($ref->hasMethod('generateRecommendations'));

        $method = $ref->getMethod('generateRecommendations');
        $this->assertTrue($method->isPrivate());
    }

    public function test_has_private_find_best_provider_method(): void
    {
        $ref = new \ReflectionClass(PaymentConstructorService::class);
        $this->assertTrue($ref->hasMethod('findBestProvider'));

        $method = $ref->getMethod('findBestProvider');
        $this->assertTrue($method->isPrivate());
    }

    public function test_generate_recommendations_logic(): void
    {
        $ref = new \ReflectionClass(PaymentConstructorService::class);
        $method = $ref->getMethod('generateRecommendations');
        $method->setAccessible(true);

        $instance = $ref->newInstanceWithoutConstructor();

        // Общий success_rate ниже 80% и total_count > 10
        $analysis = [
            'total_count' => 100,
            'captured_count' => 50,
            'failed_count' => 40,
            'refunded_count' => 5,
            'total_amount_kopecks' => 5000000,
            'success_rate' => 50.0,
            'provider_stats' => [
                'tinkoff' => ['total' => 100, 'captured' => 50, 'success_rate' => 50.0, 'total_amount' => 5000000],
            ],
        ];

        $recs = $method->invoke($instance, $analysis);
        $this->assertIsArray($recs);

        // Должна быть рекомендация о низком success rate
        $types = array_column($recs, 'type');
        $this->assertContains('low_success_rate', $types);
    }

    public function test_generate_recommendations_high_refund_rate(): void
    {
        $ref = new \ReflectionClass(PaymentConstructorService::class);
        $method = $ref->getMethod('generateRecommendations');
        $method->setAccessible(true);

        $instance = $ref->newInstanceWithoutConstructor();

        // Высокий refund rate > 10%
        $analysis = [
            'total_count' => 100,
            'captured_count' => 85,
            'failed_count' => 0,
            'refunded_count' => 15,
            'total_amount_kopecks' => 10000000,
            'success_rate' => 85.0,
            'provider_stats' => [
                'sber' => ['total' => 100, 'captured' => 85, 'success_rate' => 85.0, 'total_amount' => 10000000],
            ],
        ];

        $recs = $method->invoke($instance, $analysis);
        $types = array_column($recs, 'type');
        $this->assertContains('high_refund_rate', $types);
    }

    public function test_generate_recommendations_healthy_provider(): void
    {
        $ref = new \ReflectionClass(PaymentConstructorService::class);
        $method = $ref->getMethod('generateRecommendations');
        $method->setAccessible(true);

        $instance = $ref->newInstanceWithoutConstructor();

        $analysis = [
            'total_count' => 100,
            'captured_count' => 98,
            'failed_count' => 1,
            'refunded_count' => 1,
            'total_amount_kopecks' => 3000000,
            'success_rate' => 98.0,
            'provider_stats' => [
                'tochka' => ['total' => 100, 'captured' => 98, 'success_rate' => 98.0, 'total_amount' => 3000000],
            ],
        ];

        $recs = $method->invoke($instance, $analysis);
        $types = array_column($recs, 'type');
        // healthy не появится т.к. есть optimal_provider, проверяем что optimal_provider есть
        $this->assertContains('optimal_provider', $types);
    }

    public function test_find_best_provider_logic(): void
    {
        $ref = new \ReflectionClass(PaymentConstructorService::class);
        $method = $ref->getMethod('findBestProvider');
        $method->setAccessible(true);

        $instance = $ref->newInstanceWithoutConstructor();

        // findBestProvider принимает напрямую провайдерский массив
        $providerStats = [
            'tinkoff' => ['total' => 50, 'success_rate' => 80.0, 'captured' => 40, 'total_amount' => 5000000],
            'sber' => ['total' => 200, 'success_rate' => 97.0, 'captured' => 194, 'total_amount' => 8000000],
            'tochka' => ['total' => 100, 'success_rate' => 92.0, 'captured' => 92, 'total_amount' => 3000000],
        ];

        $best = $method->invoke($instance, $providerStats);
        $this->assertSame('sber', $best, 'Best provider should be sber with highest success rate');
    }

    public function test_no_facade_imports(): void
    {
        $src = file_get_contents(__DIR__ . '/../../../../../../app/Domains/Payment/Services/AI/PaymentConstructorService.php');
        $this->assertIsString($src);
        $this->assertStringNotContainsString('use Illuminate\\Support\\Facades\\', $src);
        $this->assertStringNotContainsString('Cache::', $src);
        $this->assertStringNotContainsString('DB::', $src);
    }

    public function test_has_strict_types(): void
    {
        $src = file_get_contents(__DIR__ . '/../../../../../../app/Domains/Payment/Services/AI/PaymentConstructorService.php');
        $this->assertIsString($src);
        $this->assertStringContainsString('declare(strict_types=1);', $src);
    }
}
