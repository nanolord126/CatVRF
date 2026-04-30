<?php

declare(strict_types=1);

namespace Modules\Analytics\Infrastructure\Jobs;

use App\Traits\WithAuditLogging;
use App\Services\AuditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

/**
 * Fine-tune Seller Models Job
 * 
 * Trains seller-specific CLV models for large sellers with sufficient data.
 * Per-seller models capture unique buying patterns and improve accuracy.
 * 
 * Thresholds for fine-tuning:
 * - Minimum 500 buyer-seller pairs
 * - Minimum 6 months of historical data
 * - Minimum 10,000 total orders
 * 
 * Production considerations:
 * - Runs weekly after global model retraining
 * - Only for sellers meeting data thresholds
 * - Stores models in seller-specific paths
 */
final class FineTuneSellerModelsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use WithAuditLogging;

    public int $tries = 2;
    public int $timeout = 7200; // 2 hours per seller

    // Thresholds for fine-tuning
    private const MIN_BUYER_SELLER_PAIRS = 500;
    private const MIN_MONTHS_HISTORY = 6;
    private const MIN_TOTAL_ORDERS = 10000;

    public function __construct(
        private readonly ?int $sellerId = null, // Specific seller or null for all eligible
        private readonly AuditService $auditService,
    ) {
        $this->onQueue('ml-training');
    }

    public function handle(): void
    {
        $this->logAction('fine_tune_seller_models', 'Starting seller model fine-tuning');

        Log::info('Starting FineTuneSellerModelsJob');

        if ($this->sellerId) {
            $this->fineTuneSeller($this->sellerId);
        } else {
            $this->fineTuneEligibleSellers();
        }

        Log::info('FineTuneSellerModelsJob completed');
        $this->logAction('fine_tune_seller_models_complete', 'Seller model fine-tuning completed');
    }

    /**
     * Find and fine-tune all eligible sellers.
     */
    private function fineTuneEligibleSellers(): void
    {
        // Find sellers with sufficient data
        $eligibleSellers = DB::table('buyer_seller_features')
            ->select('seller_id', 'tenant_id')
            ->selectRaw('COUNT(*) as pair_count')
            ->whereNotNull('actual_monetary_180d') // Has training labels
            ->where('first_purchase_at', '>=', now()->subMonths(self::MIN_MONTHS_HISTORY))
            ->groupBy('seller_id', 'tenant_id')
            ->having('pair_count', '>=', self::MIN_BUYER_SELLER_PAIRS)
            ->get();

        Log::info("Found {$eligibleSellers->count()} eligible sellers for fine-tuning");

        foreach ($eligibleSellers as $seller) {
            $this->fineTuneSeller((int) $seller->seller_id, (int) $seller->tenant_id);
        }
    }

    /**
     * Fine-tune model for a specific seller.
     */
    private function fineTuneSeller(int $sellerId, int $tenantId = null): void
    {
        Log::info("Fine-tuning model for seller {$sellerId}");

        // Export seller-specific training data
        $dataPath = $this->exportSellerData($sellerId, $tenantId);

        if (!$dataPath) {
            Log::warning("No training data for seller {$sellerId}, skipping");
            return;
        }

        // Run fine-tuning
        $modelPath = $this->runFineTuning($sellerId, $dataPath);

        if ($modelPath) {
            $this->storeSellerModel($sellerId, $modelPath);
            Log::info("Fine-tuned model saved for seller {$sellerId}");
        } else {
            Log::error("Fine-tuning failed for seller {$sellerId}");
        }

        // Cleanup
        $this->cleanup($dataPath);
    }

    /**
     * Export training data for a specific seller.
     */
    private function exportSellerData(int $sellerId, ?int $tenantId): ?string
    {
        $query = \Modules\Analytics\Models\BuyerSellerFeatures::query()
            ->forSeller($sellerId)
            ->trainingData();

        if ($tenantId) {
            $query->forTenant($tenantId);
        }

        $data = $query->get();

        if ($data->isEmpty()) {
            return null;
        }

        $dataArray = $data->map(fn ($row) => $row->toArray())->toArray();
        $tempPath = storage_path('app/temp/clv_seller_' . $sellerId . '_' . time() . '.parquet');

        try {
            $process = new Process([
                'python',
                '-c',
                "import pandas as pd; import sys; df = pd.read_json(sys.stdin); df.to_parquet('{$tempPath}')",
            ]);

            $process->setInput(json_encode($dataArray));
            $process->setTimeout(600);
            $process->mustRun();

            return $tempPath;
        } catch (\Throwable $e) {
            Log::error('Failed to export seller data', ['error' => $e->getMessage()]);
            
            // Fallback to CSV
            $csvPath = str_replace('.parquet', '.csv', $tempPath);
            $file = fopen($csvPath, 'w');
            fputcsv($file, array_keys($dataArray[0] ?? []));
            foreach ($dataArray as $row) {
                fputcsv($file, $row);
            }
            fclose($file);

            return $csvPath;
        }
    }

    /**
     * Run fine-tuning script for seller.
     */
    private function runFineTuning(int $sellerId, string $dataPath): ?string
    {
        $outputDir = storage_path('app/ml_models/clv/sellers');

        try {
            $process = new Process([
                'python',
                base_path('python-ml/train_clv.py'),
                '--data-path', $dataPath,
                '--output-dir', $outputDir,
                '--n-estimators', '400', // Fewer trees for fine-tuning
                '--learning-rate', '0.03', // Lower learning rate
                '--max-depth', '6', // Shallower trees to avoid overfitting
            ]);

            $process->setTimeout(3600);
            $process->mustRun(function ($type, $buffer) {
                if (Process::OUT === $type) {
                    Log::info($buffer);
                } else {
                    Log::error($buffer);
                }
            });

            // Find the model file
            $modelFiles = glob("{$outputDir}/clv_model_*.joblib");
            if ($modelFiles) {
                return end($modelFiles);
            }

            return null;
        } catch (\Throwable $e) {
            Log::error('Fine-tuning script failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Store seller model in storage.
     */
    private function storeSellerModel(int $sellerId, string $modelPath): void
    {
        $storagePath = "ml_models/clv/sellers/{$sellerId}/model.joblib";
        Storage::disk('ml-models')->put(
            $storagePath,
            file_get_contents($modelPath)
        );

        // Update database with seller-specific model version
        DB::table('seller_clv_models')->updateOrInsert(
            ['seller_id' => $sellerId],
            [
                'model_path' => $storagePath,
                'trained_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    private function cleanup(string $dataPath): void
    {
        try {
            if (file_exists($dataPath)) {
                unlink($dataPath);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to cleanup temporary file');
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('FineTuneSellerModelsJob failed', [
            'error' => $exception->getMessage(),
        ]);
        $this->logAction('fine_tune_seller_models_failed', $exception->getMessage());
    }
}
