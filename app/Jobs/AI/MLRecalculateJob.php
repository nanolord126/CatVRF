<?php declare(strict_types=1);

namespace App\Jobs\AI;


use App\Services\FraudMLService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

final class MLRecalculateJob implements ShouldQueue
{
    use \Illuminate\Foundation\Bus\Dispatchable, \Illuminate\Queue\InteractsWithQueue, \Illuminate\Bus\Queueable, \Illuminate\Queue\SerializesModels;

    private string $correlationId;

    public function __construct(
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
    )
    {
        $this->correlationId = Str::uuid()->toString();
        $this->onQueue('ml-training');
    }

    public function tags(): array
    {
        return ['ml', 'fraud', 'training', 'model-recalculation'];
    }

    public function retryUntil(): \DateTime
    {
        return now()->addHours(12);
    }

    public function handle(FraudMLService $fraudMLService): void
    {
        try {
            $this->db->transaction(function () use ($fraudMLService) {
                $trainingData = $fraudMLService->gatherTrainingData(
                    dateFrom: Carbon::now()->subDays(30),
                    dateTo: Carbon::now()
                );

                if ($trainingData->isEmpty()) {
                    $this->logger->channel('audit')->warning('Insufficient data for ML model training', [
                        'correlation_id' => $this->correlationId,
                        'date_range' => '30 days',
                    ]);

                    return;
                }

                $modelVersion = $fraudMLService->trainModel($trainingData);

                $metrics = $fraudMLService->evaluateModel($modelVersion);

                $this->db->table('fraud_model_versions')->insert([
                    'version' => $modelVersion,
                    'trained_at' => Carbon::now(),
                    'accuracy' => $metrics['accuracy'],
                    'precision' => $metrics['precision'],
                    'recall' => $metrics['recall'],
                    'f1_score' => $metrics['f1_score'],
                    'auc_roc' => $metrics['auc_roc'],
                    'file_path' => "storage/models/fraud/{$modelVersion}.joblib",
                    'comment' => "Auto-trained on " . Carbon::now()->toDateString(),
                ]);

                // Switch to new model if performance improved
                $currentVersion = $fraudMLService->getCurrentModelVersion();
                $currentMetrics = $fraudMLService->getModelMetrics($currentVersion);

                if ($metrics['auc_roc'] > ($currentMetrics['auc_roc'] + 0.02)) {
                    $fraudMLService->switchToModel($modelVersion);

                    $this->logger->channel('audit')->info('ML model switched to new version', [
                        'correlation_id' => $this->correlationId,
                        'old_version' => $currentVersion,
                        'new_version' => $modelVersion,
                        'old_auc' => $currentMetrics['auc_roc'],
                        'new_auc' => $metrics['auc_roc'],
                    ]);
                } else {
                    $this->logger->channel('audit')->info('ML model training completed - performance not improved', [
                        'correlation_id' => $this->correlationId,
                        'new_version' => $modelVersion,
                        'auc_roc' => $metrics['auc_roc'],
                    ]);
                }
            });
        } catch (\Exception $e) {
            $this->logger->channel('audit')->error($e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'correlation_id' => $this->correlationId,
            ]);

            $this->logger->channel('audit')->error('ML recalculation job failed', [
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}

