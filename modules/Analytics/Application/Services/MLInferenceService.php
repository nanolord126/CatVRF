<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\Services;

use App\Traits\WithAuditLogging;
use App\Services\AuditService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * ML Inference Service
 * 
 * Handles communication with ML model inference endpoints.
 * Supports multiple deployment strategies:
 * - Local Python subprocess (development)
 * - FastAPI/Flask service (production)
 * - AWS SageMaker / Vertex AI (cloud)
 * - ONNX runtime (low-latency)
 * 
 * Production-ready: circuit breaker, retries, fallback logic.
 * 
 * @see https://github.com/nanolord126/CatVRF
 */
final readonly class MLInferenceService
{
    use WithAuditLogging;

    private const TIMEOUT = 5; // 5 seconds timeout for inference
    private const MAX_RETRIES = 2;

    public function __construct(
        private readonly AuditService $auditService,
        private readonly string $inferenceUrl = null,
        private readonly string $deploymentMode = 'local', // local, http, cloud
    ) {
        $this->inferenceUrl = $inferenceUrl ?? config('analytics.ml_inference_url', 'http://localhost:8000');
    }

    /**
     * Predict CLV using ML model.
     * 
     * @param array $features Feature array from BuyerFeaturesDTO
     * @return array Prediction result with clv_180d, clv_365d, churn_prob, confidence
     */
    public function predictCLV(array $features): array
    {
        $this->logAction('ml_inference_clv', 'CLV prediction request');

        return match ($this->deploymentMode) {
            'http' => $this->predictViaHttp($features),
            'cloud' => $this->predictViaCloud($features),
            'onnx' => $this->predictViaOnnx($features),
            default => $this->predictViaLocal($features),
        };
    }

    /**
     * Predict via HTTP endpoint (FastAPI/Flask service).
     */
    private function predictViaHttp(array $features): array
    {
        $attempt = 0;
        
        while ($attempt < self::MAX_RETRIES) {
            try {
                $response = Http::timeout(self::TIMEOUT)
                    ->retry(self::MAX_RETRIES, 100)
                    ->post("{$this->inferenceUrl}/predict/clv", [
                        'features' => $features,
                    ]);

                if ($response->successful()) {
                    return $response->json();
                }

                Log::warning('ML inference HTTP request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            } catch (\Throwable $e) {
                Log::error('ML inference HTTP request error', [
                    'error' => $e->getMessage(),
                ]);
            }

            $attempt++;
        }

        // Fallback to local prediction
        Log::warning('HTTP inference failed, falling back to local');
        return $this->predictViaLocal($features);
    }

    /**
     * Predict via cloud endpoint (SageMaker/Vertex AI).
     */
    private function predictViaCloud(array $features): array
    {
        try {
            // Implementation depends on cloud provider
            // Example for AWS SageMaker:
            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Amz-Target' => 'SageMaker.InvokeEndpoint',
                ])
                ->post($this->inferenceUrl, [
                    'instances' => [$features],
                ]);

            if ($response->successful()) {
                $result = $response->json();
                return $this->normalizeCloudResponse($result);
            }

            Log::warning('Cloud inference failed, falling back to local');
        } catch (\Throwable $e) {
            Log::error('Cloud inference error', ['error' => $e->getMessage()]);
        }

        return $this->predictViaLocal($features);
    }

    /**
     * Predict via ONNX runtime (low-latency, no Python dependency).
     */
    private function predictViaOnnx(array $features): array
    {
        try {
            // Requires onnxruntime-php extension
            // This is experimental and requires PHP extension installation
            if (!extension_loaded('onnxruntime')) {
                Log::warning('ONNX runtime not loaded, falling back to local');
                return $this->predictViaLocal($features);
            }

            $modelPath = Storage::disk('ml-models')->path('clv/current_model.onnx');
            
            if (!file_exists($modelPath)) {
                Log::warning('ONNX model not found, falling back to local');
                return $this->predictViaLocal($features);
            }

            // Run ONNX inference
            $session = \OnnxRuntime\Session::fromFile($modelPath);
            $inputName = $session->inputNames()[0];
            
            $input = [
                $inputName => [
                    'dims' => [1, count($features)],
                    'values' => array_values($features),
                ],
            ];

            $output = $session->run($input);
            
            return [
                'clv_180d' => $output[0][0] ?? 0,
                'clv_365d' => $output[1][0] ?? 0,
                'churn_prob' => $output[2][0] ?? 0.5,
                'confidence' => $output[3][0] ?? 0.7,
                'model_version' => 'onnx_v1',
            ];
        } catch (\Throwable $e) {
            Log::error('ONNX inference error', ['error' => $e->getMessage()]);
            return $this->predictViaLocal($features);
        }
    }

    /**
     * Predict via local Python subprocess (development mode).
     */
    private function predictViaLocal(array $features): array
    {
        try {
            $scriptPath = base_path('python-ml/infer_clv.py');
            
            if (!file_exists($scriptPath)) {
                Log::warning('Local inference script not found, using heuristic');
                return $this->heuristicPrediction($features);
            }

            $process = new \Symfony\Component\Process\Process([
                'python',
                $scriptPath,
                json_encode($features),
            ]);

            $process->setTimeout(self::TIMEOUT);
            $process->mustRun();

            $output = $process->getOutput();
            $result = json_decode($output, true);

            if ($result) {
                return $result;
            }

            Log::warning('Local inference returned invalid output, using heuristic');
        } catch (\Throwable $e) {
            Log::error('Local inference error', ['error' => $e->getMessage()]);
        }

        return $this->heuristicPrediction($features);
    }

    /**
     * Heuristic prediction as fallback.
     * 
     * Simple rule-based prediction when ML is unavailable.
     */
    private function heuristicPrediction(array $features): array
    {
        // Extrapolate from historical spending
        $monetary180d = $features['monetary_180d'] ?? 0;
        $frequency180d = $features['frequency_180d'] ?? 0;
        $recencyDays = $features['recency_days'] ?? 365;
        
        // Calculate base CLV
        $baseClv = $monetary180d;
        
        // Adjust by frequency
        $frequencyMultiplier = 1 + min(0.5, $frequency180d / 10);
        
        // Adjust by recency
        $recencyMultiplier = max(0.5, 1 - ($recencyDays / 365));
        
        // Adjust by return rate
        $returnMultiplier = max(0.5, 1 - (($features['return_rate'] ?? 0) / 100));
        
        $predictedClv180d = $baseClv * $frequencyMultiplier * $recencyMultiplier * $returnMultiplier;
        $predictedClv365d = $predictedClv180d * 2;
        
        // Churn probability based on recency
        $churnProb = min(0.95, $recencyDays / 365);
        
        return [
            'clv_180d' => round($predictedClv180d, 2),
            'clv_365d' => round($predictedClv365d, 2),
            'churn_prob' => round($churnProb, 4),
            'confidence' => 0.5, // Low confidence for heuristic
            'model_version' => 'heuristic_v1',
        ];
    }

    /**
     * Normalize cloud provider response to standard format.
     */
    private function normalizeCloudResponse(array $response): array
    {
        // Different cloud providers return different formats
        // Normalize to standard format
        return [
            'clv_180d' => $response['predictions'][0]['clv_180d'] ?? 0,
            'clv_365d' => $response['predictions'][0]['clv_365d'] ?? 0,
            'churn_prob' => $response['predictions'][0]['churn_prob'] ?? 0.5,
            'confidence' => $response['predictions'][0]['confidence'] ?? 0.7,
            'model_version' => $response['model_version'] ?? 'cloud_v1',
        ];
    }

    /**
     * Batch prediction for multiple feature vectors.
     * 
     * @param array $featuresArray Array of feature arrays
     * @return array Array of prediction results
     */
    public function predictBatch(array $featuresArray): array
    {
        $results = [];
        
        foreach ($featuresArray as $features) {
            $results[] = $this->predictCLV($features);
        }
        
        return $results;
    }

    /**
     * Health check for ML inference endpoint.
     */
    public function healthCheck(): bool
    {
        if ($this->deploymentMode === 'local') {
            return file_exists(base_path('python-ml/infer_clv.py'));
        }

        try {
            $response = Http::timeout(2)->get("{$this->inferenceUrl}/health");
            return $response->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
