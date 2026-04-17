<?php declare(strict_types=1);

namespace App\Domains\Fashion\Services;

use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * A/B Price Testing System для Fashion.
 * PRODUCTION MANDATORY — канон CatVRF 2026.
 * 
 * Тестирование ценовых стратегий, анализ конверсии,
        определение оптимальной цены, статистическая значимость.
 */
final readonly class FashionABPriceTestingService
{
    private const MIN_SAMPLE_SIZE = 100;
    private const CONFIDENCE_LEVEL = 0.95;
    private const MAX_TEST_DAYS = 30;

    public function __construct(
        private AuditService $audit,
        private FraudControlService $fraud,
        private \Illuminate\Database\DatabaseManager $db,
    ) {}

    /**
     * Создать A/B тест цены.
     */
    public function createPriceTest(
        int $productId,
        float $controlPrice,
        float $testPrice,
        int $durationDays = 14,
        string $correlationId = ''
    ): array {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $this->fraud->check(
            userId: 0,
            operationType: 'fashion_ab_price_test_create',
            amount: 0,
            correlationId: $correlationId
        );

        $product = $this->db->table('fashion_products')
            ->where('id', $productId)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($product === null) {
            throw new \InvalidArgumentException('Product not found', 404);
        }

        $testId = $this->db->table('fashion_ab_price_tests')->insertGetId([
            'tenant_id' => $tenantId,
            'product_id' => $productId,
            'control_price' => $controlPrice,
            'test_price' => $testPrice,
            'control_group_size' => 0,
            'test_group_size' => 0,
            'control_conversions' => 0,
            'test_conversions' => 0,
            'control_revenue' => 0,
            'test_revenue' => 0,
            'status' => 'active',
            'started_at' => Carbon::now(),
            'ends_at' => Carbon::now()->addDays(min($durationDays, self::MAX_TEST_DAYS)),
            'correlation_id' => $correlationId,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $this->audit->record(
            action: 'fashion_ab_price_test_created',
            subjectType: 'fashion_ab_price_test',
            subjectId: $testId,
            oldValues: [],
            newValues: [
                'product_id' => $productId,
                'control_price' => $controlPrice,
                'test_price' => $testPrice,
                'duration_days' => $durationDays,
            ],
            correlationId: $correlationId
        );

        Log::channel('audit')->info('Fashion A/B price test created', [
            'test_id' => $testId,
            'tenant_id' => $tenantId,
            'product_id' => $productId,
            'correlation_id' => $correlationId,
        ]);

        return [
            'test_id' => $testId,
            'product_id' => $productId,
            'control_price' => $controlPrice,
            'test_price' => $testPrice,
            'status' => 'active',
            'ends_at' => Carbon::now()->addDays(min($durationDays, self::MAX_TEST_DAYS))->toIso8601String(),
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Записать конверсию в A/B тесте.
     */
    public function recordConversion(
        int $testId,
        int $userId,
        string $group,
        float $price,
        string $correlationId = ''
    ): array {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $test = $this->db->table('fashion_ab_price_tests')
            ->where('id', $testId)
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->first();

        if ($test === null) {
            throw new \InvalidArgumentException('Active test not found', 404);
        }

        $this->db->table('fashion_ab_price_test_conversions')->insert([
            'test_id' => $testId,
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'group' => $group,
            'price' => $price,
            'converted_at' => Carbon::now(),
            'correlation_id' => $correlationId,
        ]);

        $column = $group === 'control' ? 'control' : 'test';
        $this->db->table('fashion_ab_price_tests')
            ->where('id', $testId)
            ->increment($column . '_conversions');
        
        $this->db->table('fashion_ab_price_tests')
            ->where('id', $testId)
            ->increment($column . '_revenue', $price);

        return [
            'test_id' => $testId,
            'user_id' => $userId,
            'group' => $group,
            'recorded' => true,
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Получить результаты A/B теста.
     */
    public function getTestResults(int $testId, string $correlationId = ''): array
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $test = $this->db->table('fashion_ab_price_tests')
            ->where('id', $testId)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($test === null) {
            throw new \InvalidArgumentException('Test not found', 404);
        }

        $controlRate = $test['control_group_size'] > 0
            ? $test['control_conversions'] / $test['control_group_size']
            : 0;
        
        $testRate = $test['test_group_size'] > 0
            ? $test['test_conversions'] / $test['test_group_size']
            : 0;

        $lift = $controlRate > 0 ? (($testRate - $controlRate) / $controlRate) * 100 : 0;
        $statisticalSignificance = $this->calculateStatisticalSignificance(
            $test['control_conversions'],
            $test['control_group_size'],
            $test['test_conversions'],
            $test['test_group_size']
        );

        $winner = $this->determineWinner($lift, $statisticalSignificance);

        if ($test['status'] === 'active' && Carbon::now()->gt($test['ends_at'])) {
            $this->completeTest($testId, $winner, $correlationId);
        }

        return [
            'test_id' => $testId,
            'status' => $test['status'],
            'control_price' => $test['control_price'],
            'test_price' => $test['test_price'],
            'control_group' => [
                'size' => $test['control_group_size'],
                'conversions' => $test['control_conversions'],
                'conversion_rate' => round($controlRate * 100, 2),
                'revenue' => $test['control_revenue'],
            ],
            'test_group' => [
                'size' => $test['test_group_size'],
                'conversions' => $test['test_conversions'],
                'conversion_rate' => round($testRate * 100, 2),
                'revenue' => $test['test_revenue'],
            ],
            'lift_percentage' => round($lift, 2),
            'statistical_significance' => round($statisticalSignificance * 100, 2),
            'winner' => $winner,
            'recommendation' => $this->getRecommendation($winner, $statisticalSignificance, $lift),
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Получить активные тесты.
     */
    public function getActiveTests(string $correlationId = ''): array
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $tests = $this->db->table('fashion_ab_price_tests')
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->orderBy('started_at', 'desc')
            ->get()
            ->toArray();

        return [
            'tenant_id' => $tenantId,
            'active_tests' => $tests,
            'total_count' => count($tests),
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Остановить тест.
     */
    public function stopTest(int $testId, string $correlationId = ''): array
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $test = $this->db->table('fashion_ab_price_tests')
            ->where('id', $testId)
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->first();

        if ($test === null) {
            throw new \InvalidArgumentException('Active test not found', 404);
        }

        $results = $this->getTestResults($testId, $correlationId);
        $this->completeTest($testId, $results['winner'], $correlationId);

        $this->audit->record(
            action: 'fashion_ab_price_test_stopped',
            subjectType: 'fashion_ab_price_test',
            subjectId: $testId,
            oldValues: [],
            newValues: [
                'winner' => $results['winner'],
                'lift' => $results['lift_percentage'],
            ],
            correlationId: $correlationId
        );

        return [
            'test_id' => $testId,
            'stopped' => true,
            'results' => $results,
            'correlation_id' => $correlationId,
        ];
    }

    private function calculateStatisticalSignificance(
        int $controlConversions,
        int $controlSize,
        int $testConversions,
        int $testSize
    ): float {
        if ($controlSize < self::MIN_SAMPLE_SIZE || $testSize < self::MIN_SAMPLE_SIZE) {
            return 0.0;
        }

        $p1 = $controlConversions / $controlSize;
        $p2 = $testConversions / $testSize;
        
        $pooled = ($controlConversions + $testConversions) / ($controlSize + $testSize);
        $se = sqrt($pooled * (1 - $pooled) * (1 / $controlSize + 1 / $testSize));
        
        if ($se === 0) {
            return 0.0;
        }

        $z = ($p2 - $p1) / $se;
        
        return abs($z) >= 1.96 ? 0.95 : abs($z) >= 1.64 ? 0.90 : 0.0;
    }

    private function determineWinner(float $lift, float $statisticalSignificance): string
    {
        if ($statisticalSignificance < 0.9) {
            return 'inconclusive';
        }

        if ($lift > 0) {
            return 'test';
        } elseif ($lift < 0) {
            return 'control';
        }

        return 'tie';
    }

    private function getRecommendation(string $winner, float $statisticalSignificance, float $lift): string
    {
        if ($winner === 'inconclusive') {
            return 'Insufficient data for conclusion. Continue test or extend duration.';
        }

        if ($winner === 'test') {
            return sprintf(
                'Test price is statistically superior with %.2f%% lift. Recommended to implement.',
                $lift
            );
        }

        if ($winner === 'control') {
            return sprintf(
                'Control price performs better. Keep current pricing strategy.',
                abs($lift)
            );
        }

        return 'No significant difference between prices.';
    }

    private function completeTest(int $testId, string $winner, string $correlationId): void
    {
        $this->db->table('fashion_ab_price_tests')
            ->where('id', $testId)
            ->update([
                'status' => 'completed',
                'winner' => $winner,
                'completed_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
    }

    private function getTenantId(): int
    {
        return function_exists('tenant') && tenant() ? tenant()->id : 1;
    }
}
