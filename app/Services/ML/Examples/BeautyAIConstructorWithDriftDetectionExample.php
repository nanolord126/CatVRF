<?php

declare(strict_types=1);

namespace App\Services\ML\Examples;

use App\Services\ML\AbstractAIConstructorService;
use App\Services\ML\FeatureDriftDetectorService;
use App\Services\ML\FeatureDriftMetricsService;
use Illuminate\Support\Facades\Log;

/**
 * Example AI Constructor Service with Feature Drift Detection
 * 
 * This is a template showing how to integrate drift detection into any vertical's AI service.
 * Replace 'beauty' with your vertical code and customize monitored features.
 * 
 * To use this template:
 * 1. Copy this file to your vertical's Services/AI directory
 * 2. Rename class to match your vertical (e.g., FoodAIConstructorService)
 * 3. Update $verticalCode
 * 4. Customize monitored features in config/fraud.php
 * 5. Add drift checks in your AI methods
 */
final class BeautyAIConstructorWithDriftDetectionExample extends AbstractAIConstructorService
{
    protected string $verticalCode = 'beauty';

    /**
     * Example AI method with drift detection
     * 
     * @param string $userPrompt
     * @return array
     */
    public function generateBeautyRecommendation(string $userPrompt): array
    {
        // Check drift for key features before AI inference
        $this->checkFeatureDrift('prompt_length', strlen($userPrompt));
        $this->checkFeatureDrift('user_language', $this->detectLanguage($userPrompt));

        // Your existing AI logic here
        $recommendation = $this->callAIService($userPrompt);

        return $recommendation;
    }

    /**
     * Store reference distributions after model training
     * Call this from your ML retraining job
     * 
     * @param string $modelVersion
     * @return void
     */
    public function storeTrainingDataDistributions(string $modelVersion): void
    {
        // Collect sample data from recent inferences
        $features = [
            'prompt_length' => $this->getRecentPromptLengths(),
            'user_language' => $this->getRecentLanguages(),
            'request_type' => $this->getRecentRequestTypes(),
            'user_segment' => $this->getRecentUserSegments(),
        ];

        $this->storeReferenceDistributions($modelVersion, $features);
        
        Log::info('Reference distributions stored for Beauty vertical', [
            'model_version' => $modelVersion,
            'features_count' => count($features),
        ]);
    }

    /**
     * Batch drift check for multiple features
     * Call this periodically (e.g., daily) from a scheduled job
     * 
     * @return array
     */
    public function performScheduledDriftCheck(): array
    {
        $currentFeatures = [
            'prompt_length' => $this->getRecentPromptLengths(),
            'user_language' => $this->getRecentLanguages(),
            'request_type' => $this->getRecentRequestTypes(),
            'user_segment' => $this->getRecentUserSegments(),
        ];

        $driftReport = $this->checkMultipleFeaturesDrift($currentFeatures);
        
        // Cache for monitoring
        cache(['beauty_drift_report' => $driftReport], now()->addHours(24));
        
        $this->logDriftResults($driftReport);

        return $driftReport;
    }

    // ========== Helper Methods (customize for your vertical) ==========

    private function callAIService(string $prompt): array
    {
        // Your actual AI service call
        return [];
    }

    private function detectLanguage(string $text): string
    {
        // Language detection logic
        return 'ru';
    }

    private function getRecentPromptLengths(): array
    {
        // Query recent prompts from your database/cache
        return [100, 150, 200, 120, 180];
    }

    private function getRecentLanguages(): array
    {
        // Query recent languages
        return ['ru', 'ru', 'ru', 'en', 'ru'];
    }

    private function getRecentRequestTypes(): array
    {
        // Query recent request types
        return ['consultation', 'recommendation', 'consultation'];
    }

    private function getRecentUserSegments(): array
    {
        // Query recent user segments
        return ['premium', 'standard', 'premium'];
    }
}
