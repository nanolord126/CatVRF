<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Analytics\Application\DTOs\BuyerFeaturesDTO;
use Modules\Analytics\Application\DTOs\CLVPredictionDTO;
use Modules\Analytics\Application\Services\SellerCLVService;
use Modules\Analytics\Models\BuyerSellerFeatures;

final class TestCLVImplementation extends Command
{
    protected $signature = 'clv:test {--seller-id= : Specific seller ID to test}';
    protected $description = 'Test CLV implementation - verify components work correctly';

    public function handle(): int
    {
        $this->info('Testing CLV Implementation...');
        $this->newLine();

        $results = [
            'database' => $this->testDatabase(),
            'model' => $this->testModel(),
            'service' => $this->testService(),
            'dto' => $this->testDTOs(),
        ];

        $this->newLine();
        $this->info('Test Results:');
        foreach ($results as $component => $passed) {
            $status = $passed ? '✓ PASSED' : '✗ FAILED';
            $color = $passed ? 'green' : 'red';
            $this->line("  {$component}: {$status}", $color);
        }

        $allPassed = array_reduce($results, fn ($carry, $item) => $carry && $item, true);

        $this->newLine();
        if ($allPassed) {
            $this->info('All tests passed! CLV implementation is ready.');
            return self::SUCCESS;
        } else {
            $this->error('Some tests failed. Please review the errors above.');
            return self::FAILURE;
        }
    }

    private function testDatabase(): bool
    {
        $this->info('Testing database...');

        try {
            // Check if table exists
            if (!DB::getSchemaBuilder()->hasTable('buyer_seller_features')) {
                $this->error('  ✗ buyer_seller_features table does not exist');
                $this->warn('  Run: php artisan migrate');
                return false;
            }

            $this->info('  ✓ buyer_seller_features table exists');

            // Check table structure
            $columns = DB::getSchemaBuilder()->getColumnListing('buyer_seller_features');
            $requiredColumns = [
                'buyer_id', 'seller_id', 'tenant_id',
                'r_score', 'f_score', 'm_score',
                'predicted_clv_180d', 'churn_probability', 'clv_segment',
            ];

            foreach ($requiredColumns as $column) {
                if (!in_array($column, $columns)) {
                    $this->error("  ✗ Missing column: {$column}");
                    return false;
                }
            }

            $this->info('  ✓ All required columns present');

            // Check indexes
            $indexes = DB::select("SHOW INDEX FROM buyer_seller_features");
            $indexNames = array_column($indexes, 'Key_name');
            
            if (!in_array('idx_seller_clv', $indexNames)) {
                $this->warn('  ⚠ Index idx_seller_clv not found (optional)');
            } else {
                $this->info('  ✓ Performance indexes present');
            }

            return true;
        } catch (\Throwable $e) {
            $this->error("  ✗ Database error: {$e->getMessage()}");
            return false;
        }
    }

    private function testModel(): bool
    {
        $this->info('Testing model...');

        try {
            // Test model instantiation
            $model = new BuyerSellerFeatures();
            $this->info('  ✓ BuyerSellerFeatures model instantiable');

            // Test scopes
            $query = BuyerSellerFeatures::query()->forSeller(1);
            $this->info('  ✓ Model scopes work');

            return true;
        } catch (\Throwable $e) {
            $this->error("  ✗ Model error: {$e->getMessage()}");
            return false;
        }
    }

    private function testService(): bool
    {
        $this->info('Testing service...');

        try {
            $service = app(SellerCLVService::class);
            $this->info('  ✓ SellerCLVService instantiable');

            // Test with mock data
            $prediction = $service->predictForBuyer(1, 1, 1);
            
            if (!$prediction instanceof CLVPredictionDTO) {
                $this->error('  ✗ predictForBuyer did not return CLVPredictionDTO');
                return false;
            }

            $this->info('  ✓ predictForBuyer returns correct DTO');
            $this->info("  ✓ Sample prediction: CLV={$prediction->predictedClv180d}, Churn={$prediction->churnProbability}");

            // Test aggregated metrics
            $metrics = $service->getAggregatedCLVMetrics(1, 1);
            $this->info('  ✓ getAggregatedCLVMetrics works');

            return true;
        } catch (\Throwable $e) {
            $this->error("  ✗ Service error: {$e->getMessage()}");
            $this->error("  Stack trace: {$e->getTraceAsString()}");
            return false;
        }
    }

    private function testDTOs(): bool
    {
        $this->info('Testing DTOs...');

        try {
            // Test BuyerFeaturesDTO
            $features = new BuyerFeaturesDTO(
                buyerId: 1,
                sellerId: 1,
                tenantId: 1,
                rScore: 4,
                fScore: 3,
                mScore: 5,
                recencyDays: 10,
                lastPurchaseAt: new \DateTimeImmutable(),
                frequency90d: 2,
                frequency180d: 5,
                frequency365d: 10,
                monetary90d: 5000.0,
                monetary180d: 15000.0,
                monetary365d: 30000.0,
                avgOrderValue: 3000.0,
                firstPurchaseAt: new \DateTimeImmutable('-6 months'),
                daysSinceFirstPurchase: 180,
                totalOrdersAllTime: 10,
                totalMonetaryAllTime: 30000.0,
                returnRate: 5.0,
                reviewScore: 4.5,
                totalReviews: 3,
                trafficSearchPct: 40.0,
                trafficRecommendationPct: 30.0,
                trafficDirectPct: 20.0,
                trafficOtherPct: 10.0,
                lastCategory: 'electronics',
                geoRegion: 'Moscow',
                geoCity: 'Moscow',
            );

            $featureArray = $features->toFeatureArray();
            $this->info('  ✓ BuyerFeaturesDTO works');

            // Test CLVPredictionDTO
            $prediction = new CLVPredictionDTO(
                buyerId: 1,
                sellerId: 1,
                tenantId: 1,
                predictedClv180d: 25000.0,
                predictedClv365d: 50000.0,
                churnProbability: 0.3,
                confidence: 0.85,
                segment: 'high',
            );

            $this->info('  ✓ CLVPredictionDTO works');
            $this->info('  ✓ isHighChurnRisk: ' . ($prediction->isHighChurnRisk() ? 'true' : 'false'));
            $this->info('  ✓ isVip: ' . ($prediction->isVip() ? 'true' : 'false'));

            return true;
        } catch (\Throwable $e) {
            $this->error("  ✗ DTO error: {$e->getMessage()}");
            return false;
        }
    }
}
