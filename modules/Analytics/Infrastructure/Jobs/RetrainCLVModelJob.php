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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

/**
 * Retrain CLV Model Job
 * 
 * Trains the XGBoost model for CLV prediction using updated feature data.
 * Runs weekly (or on-demand) to keep the model current with market changes.
 * 
 * Production considerations:
 * - Exports training data to parquet for Python ML pipeline
 * - Calls Python training script asynchronously
 * - Stores model artifacts in S3/storage
 * - Updates model version in feature store
 * 
 * @see https://github.com/nanolord126/CatVRF
 */
final readonly class RetrainCLVModelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use WithAuditLogging;

    public int $tries = 2;
    public int $timeout = 7200; // 2 hours

    public function __construct(
        private readonly ?int $tenantId = null,
        private readonly AuditService $auditService,
    ) {
        $this->onQueue('ml-training');
    }

    public function handle(): void
    {
        $this->logAction('retrain_clv_model', 'Starting CLV model retraining');

        Log::info('Starting RetrainCLVModelJob');

        // Export training data from database
        $dataPath = $this->exportTrainingData();

        if (!$dataPath) {
            Log::warning('No training data available, skipping retraining');
            return;
        }

        // Run Python training script
        $modelPath = $this->runTrainingScript($dataPath);

        if ($modelPath) {
            // Update model version in database
            $this->updateModelVersion($modelPath);
            
            Log::info('CLV model retraining completed successfully');
            $this->logAction('retrain_clv_model_complete', "Model saved to {$modelPath}");
        } else {
            Log::error('CLV model retraining failed');
            $this->logAction('retrain_clv_model_failed', 'Training script failed');
        }

        // Cleanup temporary files
        $this->cleanup($dataPath);
    }

    /**
     * Export training data from database to parquet file.
     */
    private function exportTrainingData(): ?string
    {
        Log::info('Exporting training data from database');

        $query = \Modules\Analytics\Models\BuyerSellerFeatures::query()
            ->trainingData(); // Only rows with actual labels

        if ($this->tenantId) {
            $query->forTenant($this->tenantId);
        }

        $data = $query->get();

        if ($data->isEmpty()) {
            Log::warning('No training data found');
            return null;
        }

        // Convert to array for export
        $dataArray = $data->map(fn ($row) => $row->toArray())->toArray();

        // Create temporary parquet file
        $tempPath = storage_path('app/temp/clv_training_data_' . time() . '.parquet');
        
        try {
            // Use pandas to write parquet (requires python)
            $process = new Process([
                'python',
                '-c',
                "import pandas as pd; import sys; df = pd.read_json(sys.stdin); df.to_parquet('{$tempPath}')",
            ]);
            
            $process->setInput(json_encode($dataArray));
            $process->setTimeout(600);
            $process->mustRun();

            Log::info("Exported {$data->count()} training samples to {$tempPath}");
            return $tempPath;
        } catch (\Throwable $e) {
            Log::error('Failed to export training data', ['error' => $e->getMessage()]);
            
            // Fallback: export as CSV
            $csvPath = str_replace('.parquet', '.csv', $tempPath);
            $file = fopen($csvPath, 'w');
            fputcsv($file, array_keys($dataArray[0] ?? []));
            foreach ($dataArray as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
            
            Log::info("Exported to CSV fallback: {$csvPath}");
            return $csvPath;
        }
    }

    /**
     * Run Python training script.
     */
    private function runTrainingScript(string $dataPath): ?string
    {
        Log::info('Running Python training script');

        $outputDir = storage_path('app/ml_models/clv');
        
        try {
            $process = new Process([
                'python',
                base_path('python-ml/train_clv.py'),
                '--data-path', $dataPath,
                '--output-dir', $outputDir,
                '--n-estimators', '800',
                '--learning-rate', '0.05',
                '--max-depth', '8',
            ]);

            $process->setTimeout(3600);
            $process->mustRun(function ($type, $buffer) {
                if (Process::OUT === $type) {
                    Log::info($buffer);
                } else {
                    Log::error($buffer);
                }
            });

            // Extract model path from output
            $output = $process->getOutput();
            if (preg_match('/Model saved to: (.+)/', $output, $matches)) {
                return trim($matches[1]);
            }

            // Fallback: find latest model file
            $modelFiles = glob("{$outputDir}/clv_model_*.joblib");
            if ($modelFiles) {
                return end($modelFiles);
            }

            return null;
        } catch (\Throwable $e) {
            Log::error('Training script failed', [
                'error' => $e->getMessage(),
                'output' => $e->getOutput() ?? '',
            ]);
            return null;
        }
    }

    /**
     * Update model version in database or config.
     */
    private function updateModelVersion(string $modelPath): void
    {
        $version = basename($modelPath, '.joblib');
        
        // Store model version in database or config
        // TODO: Create a model_versions table for tracking
        Log::info("Updated model version to: {$version}");
        
        // Store in storage for retrieval
        Storage::disk('ml-models')->put(
            "clv/current_model.joblib",
            file_get_contents($modelPath)
        );
    }

    /**
     * Cleanup temporary files.
     */
    private function cleanup(string $dataPath): void
    {
        try {
            if (file_exists($dataPath)) {
                unlink($dataPath);
                Log::info("Cleaned up temporary file: {$dataPath}");
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to cleanup temporary file', ['error' => $e->getMessage()]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('RetrainCLVModelJob failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
        $this->logAction('retrain_clv_model_failed', $exception->getMessage());
    }
}
