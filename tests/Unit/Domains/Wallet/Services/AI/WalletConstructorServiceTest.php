<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Wallet\Services\AI;

use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Services\AI\WalletConstructorService;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

/**
 * Unit-тесты AI WalletConstructorService.
 *
 * FraudControlService, AuditService — final classes → newInstanceWithoutConstructor().
 * Wallet — final class → newInstanceWithoutConstructor() + attributes через Reflection.
 */
final class WalletConstructorServiceTest extends TestCase
{
    public function test_can_instantiate(): void
    {
        $service = $this->createService();
        $this->assertInstanceOf(WalletConstructorService::class, $service);
    }

    public function test_generate_recommendations_returns_array_for_empty_analysis(): void
    {
        $service = $this->createService();

        $method = new \ReflectionMethod(WalletConstructorService::class, 'generateRecommendations');
        $method->setAccessible(true);

        $wallet = $this->createWalletStub(0, 0);

        $analysis = [
            'total_deposits' => 0,
            'total_withdrawals' => 0,
            'net_flow' => 0,
            'avg_transaction' => 0,
            'top_categories' => [],
        ];

        $result = $method->invoke($service, $analysis, $wallet);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function test_generate_recommendations_spending_alert_when_negative_flow(): void
    {
        $service = $this->createService();

        $method = new \ReflectionMethod(WalletConstructorService::class, 'generateRecommendations');
        $method->setAccessible(true);

        $wallet = $this->createWalletStub(10000, 0);

        $analysis = [
            'total_deposits' => 5000,
            'total_withdrawals' => 10000,
            'net_flow' => -5000,
            'avg_transaction' => 7500,
            'top_categories' => ['withdrawal'],
        ];

        $result = $method->invoke($service, $analysis, $wallet);

        $types = array_column($result, 'type');
        $this->assertContains('spending_alert', $types);
    }

    public function test_generate_recommendations_hold_alert_when_high_hold(): void
    {
        $service = $this->createService();

        $method = new \ReflectionMethod(WalletConstructorService::class, 'generateRecommendations');
        $method->setAccessible(true);

        $wallet = $this->createWalletStub(10000, 8000);

        $analysis = [
            'total_deposits' => 10000,
            'total_withdrawals' => 0,
            'net_flow' => 10000,
            'avg_transaction' => 10000,
            'top_categories' => ['deposit'],
        ];

        $result = $method->invoke($service, $analysis, $wallet);

        $types = array_column($result, 'type');
        $this->assertContains('hold_alert', $types);
    }

    public function test_generate_recommendations_healthy_when_all_good(): void
    {
        $service = $this->createService();

        $method = new \ReflectionMethod(WalletConstructorService::class, 'generateRecommendations');
        $method->setAccessible(true);

        $wallet = $this->createWalletStub(100000, 1000);

        $analysis = [
            'total_deposits' => 100000,
            'total_withdrawals' => 5000,
            'net_flow' => 95000,
            'avg_transaction' => 50000,
            'top_categories' => ['deposit'],
        ];

        $result = $method->invoke($service, $analysis, $wallet);

        $types = array_column($result, 'type');
        $this->assertContains('healthy', $types);
    }

    public function test_extract_top_categories_returns_max_three(): void
    {
        $service = $this->createService();

        $method = new \ReflectionMethod(WalletConstructorService::class, 'extractTopCategories');
        $method->setAccessible(true);

        $transactions = [
            (object) ['type' => 'deposit'],
            (object) ['type' => 'deposit'],
            (object) ['type' => 'withdrawal'],
            (object) ['type' => 'withdrawal'],
            (object) ['type' => 'commission'],
            (object) ['type' => 'bonus'],
            (object) ['type' => 'bonus'],
            (object) ['type' => 'bonus'],
            (object) ['type' => 'refund'],
        ];

        $result = $method->invoke($service, $transactions);

        $this->assertCount(3, $result);
        // bonus (3) should be first
        $this->assertSame('bonus', $result[0]);
    }

    private function createService(): WalletConstructorService
    {
        return new WalletConstructorService(
            $this->createMock(DatabaseManager::class),
            $this->createMock(\Psr\Log\LoggerInterface::class),
            (new \ReflectionClass(FraudControlService::class))->newInstanceWithoutConstructor(),
            (new \ReflectionClass(AuditService::class))->newInstanceWithoutConstructor(),
            $this->createMock(CacheRepository::class),
        );
    }

    private function createWalletStub(int $balance, int $hold): Wallet
    {
        $wallet = (new \ReflectionClass(Wallet::class))->newInstanceWithoutConstructor();
        (new \ReflectionProperty(Model::class, 'attributes'))->setValue($wallet, [
            'current_balance' => $balance,
            'hold_amount' => $hold,
        ]);

        return $wallet;
    }
}
