<?php

declare(strict_types=1);

namespace App\Domains\Pharmacy\AI;

use App\Services\AI\OpenAIService;
use Illuminate\Support\Facades\Log;

final readonly class PharmacyConstructorService
{
    public function __construct(
        private OpenAIService $openAIService,
    ) {}

    /**
     * Generate AI-powered medication recommendations
     */
    public function generateRecommendations(
        string $symptoms,
        array $context = [],
    ): array {
        $prompt = $this->buildRecommendationPrompt($symptoms, $context);

        try {
            $response = $this->openAIService->chat([
                [
                    'role' => 'system',
                    'content' => 'You are a pharmaceutical expert for CatVRF marketplace. Provide general medication information only. Never provide specific medical advice or prescriptions. Always recommend consulting a healthcare professional.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ], temperature: 0.5);

            return $this->parseRecommendationResponse($response);
        } catch (\Exception $e) {
            Log::error('Pharmacy AI recommendation failed', [
                'error' => $e->getMessage(),
                'symptoms' => $this->anonymizeSymptoms($symptoms),
            ]);

            return [
                'recommendations' => [],
                'disclaimer' => 'AI recommendation service temporarily unavailable',
                'error' => true,
            ];
        }
    }

    /**
     * Check for potential drug interactions
     */
    public function checkDrugInteractions(array $medications): array
    {
        $prompt = $this->buildInteractionPrompt($medications);

        try {
            $response = $this->openAIService->chat([
                [
                    'role' => 'system',
                    'content' => 'You are a drug interaction expert. Identify potential interactions between medications. Always include severity levels and recommendations.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ], temperature: 0.3);

            return $this->parseInteractionResponse($response);
        } catch (\Exception $e) {
            Log::error('Drug interaction check failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'interactions' => [],
                'error' => true,
            ];
        }
    }

    /**
     * Suggest alternative medications (OTC only)
     */
    public function suggestAlternatives(
        string $medicationName,
        array $filters = [],
    ): array {
        $prompt = $this->buildAlternativesPrompt($medicationName, $filters);

        try {
            $response = $this->openAIService->chat([
                [
                    'role' => 'system',
                    'content' => 'You are a pharmaceutical expert. Suggest OTC alternatives to medications. Never suggest prescription-only alternatives. Always include a disclaimer.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ], temperature: 0.4);

            return $this->parseAlternativesResponse($response);
        } catch (\Exception $e) {
            Log::error('Alternative suggestions failed', [
                'error' => $e->getMessage(),
                'medication' => $medicationName,
            ]);

            return [
                'alternatives' => [],
                'error' => true,
            ];
        }
    }

    /**
     * Build prompt for recommendations
     */
    private function buildRecommendationPrompt(string $symptoms, array $context): string
    {
        $contextStr = '';

        if (!empty($context['age'])) {
            $contextStr .= "Age group: {$context['age']}\n";
        }

        if (!empty($context['allergies'])) {
            $contextStr .= "Known allergies: " . implode(', ', $context['allergies']) . "\n";
        }

        if (!empty($context['conditions'])) {
            $contextStr .= "Pre-existing conditions: " . implode(', ', $context['conditions']) . "\n";
        }

        return <<<PROMPT
Symptoms: {$symptoms}

Context:
{$contextStr}

Please provide:
1. General information about over-the-counter medications that may help
2. Important warnings and contraindications
3. When to consult a healthcare professional
4. Lifestyle recommendations

IMPORTANT: This is for informational purposes only. Always consult a healthcare professional before taking any medication.

Response format: JSON with keys: otc_suggestions, warnings, when_to_see_doctor, lifestyle_tips, disclaimer.
PROMPT;
    }

    /**
     * Build prompt for drug interactions
     */
    private function buildInteractionPrompt(array $medications): string
    {
        $medsStr = implode(', ', $medications);

        return <<<PROMPT
Medications: {$medsStr}

Please identify:
1. Potential drug-drug interactions
2. Severity level (mild, moderate, severe)
3. Recommendations for each interaction
4. Foods or activities to avoid

IMPORTANT: This is for informational purposes only. Always consult a healthcare professional or pharmacist.

Response format: JSON with keys: interactions (array with medication_pair, severity, description, recommendation), foods_to_avoid, activities_to_avoid, disclaimer.
PROMPT;
    }

    /**
     * Build prompt for alternatives
     */
    private function buildAlternativesPrompt(string $medicationName, array $filters): string
    {
        $filtersStr = !empty($filters) ? json_encode($filters, JSON_PRETTY_PRINT) : '{}';

        return <<<PROMPT
Medication: {$medicationName}
Filters: {$filtersStr}

Please suggest:
1. Over-the-counter alternatives only
2. Similar mechanism of action
3. Price range comparison
4. Availability considerations

IMPORTANT: Never suggest prescription-only alternatives. Always include a disclaimer to consult a healthcare professional.

Response format: JSON with keys: alternatives (array with name, description, pros, cons, price_range), disclaimer.
PROMPT;
    }

    /**
     * Parse recommendation response
     */
    private function parseRecommendationResponse(string $response): array
    {
        try {
            $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

            return [
                'recommendations' => $data['otc_suggestions'] ?? [],
                'warnings' => $data['warnings'] ?? [],
                'when_to_see_doctor' => $data['when_to_see_doctor'] ?? [],
                'lifestyle_tips' => $data['lifestyle_tips'] ?? [],
                'disclaimer' => $data['disclaimer'] ?? 'Always consult a healthcare professional before taking any medication.',
                'error' => false,
            ];
        } catch (\JsonException $e) {
            Log::error('Failed to parse recommendation response', [
                'error' => $e->getMessage(),
                'response' => $response,
            ]);

            return [
                'recommendations' => [],
                'disclaimer' => 'Failed to process AI response',
                'error' => true,
            ];
        }
    }

    /**
     * Parse interaction response
     */
    private function parseInteractionResponse(string $response): array
    {
        try {
            $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

            return [
                'interactions' => $data['interactions'] ?? [],
                'foods_to_avoid' => $data['foods_to_avoid'] ?? [],
                'activities_to_avoid' => $data['activities_to_avoid'] ?? [],
                'disclaimer' => $data['disclaimer'] ?? 'Always consult a healthcare professional or pharmacist.',
                'error' => false,
            ];
        } catch (\JsonException $e) {
            Log::error('Failed to parse interaction response', [
                'error' => $e->getMessage(),
                'response' => $response,
            ]);

            return [
                'interactions' => [],
                'error' => true,
            ];
        }
    }

    /**
     * Parse alternatives response
     */
    private function parseAlternativesResponse(string $response): array
    {
        try {
            $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

            return [
                'alternatives' => $data['alternatives'] ?? [],
                'disclaimer' => $data['disclaimer'] ?? 'Always consult a healthcare professional before changing medications.',
                'error' => false,
            ];
        } catch (\JsonException $e) {
            Log::error('Failed to parse alternatives response', [
                'error' => $e->getMessage(),
                'response' => $response,
            ]);

            return [
                'alternatives' => [],
                'error' => true,
            ];
        }
    }

    /**
     * Anonymize symptoms for logging (compliance)
     */
    private function anonymizeSymptoms(string $symptoms): string
    {
        // Remove specific medical details, keep only general category
        return '[ANONYMIZED] Length: ' . strlen($symptoms);
    }
}
