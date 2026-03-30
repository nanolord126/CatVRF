<?php declare(strict_types=1);

namespace App\Services\Cleaning;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AICleaningConstructor extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    private string $correlationId;

        public function __construct(
            private OpenAI\Client $openai,
            private RecommendationService $recommendation,
            private CleaningBookingService $bookingService,
            ?string $correlationId = null
        ) {
            $this->correlationId = $correlationId ?? (string) Str::uuid();
        }

        /**
         * Builds a comprehensive cleaning plan using AI for photo/text analysis.
         *
         * @param array $photoUrls URL to room photos.
         * @param string $type Type of cleaning (Standard, Deep, After Construction).
         * @param int|null $budgetMax In kopecks (cents).
         * @return array AI recommendation payload.
         */
        public function buildCleaningPlan(array $photoUrls, string $type, ?int $budgetMax = null): array
        {
            Log::channel('audit')->info('AI Cleaning Plan Generation Started', [
                'type' => $type,
                'photos_count' => count($photoUrls),
                'budget' => $budgetMax,
                'correlation_id' => $this->correlationId,
            ]);

            try {
                // 1. AI Visual Analysis (Vision Model simulation)
                $visualAnalysis = $this->analyzePhotos($photoUrls);

                // 2. Logic to match cleaning services with analysis results
                $matchedServices = $this->matchServices($visualAnalysis, $type, $budgetMax);

                // 3. Calculation of total estimated time and cost
                $estimates = $this->calculatePlanSummary($matchedServices, $visualAnalysis['area_estimation_sqm']);

                $plan = [
                    'correlation_id' => $this->correlationId,
                    'visual_findings' => $visualAnalysis['detected_objects'], // 'Windows', 'Deep stains', 'Tile grout'
                    'recommended_services' => $matchedServices,
                    'estimated_sqm' => $visualAnalysis['area_estimation_sqm'],
                    'estimated_total_cents' => $estimates['total_cents'],
                    'estimated_duration_min' => $estimates['duration_min'],
                    'prepayment_cents' => $estimates['prepayment_cents'],
                    'ai_confidence' => 0.94,
                ];

                // 4. Final Logging for audit trace
                Log::channel('audit')->info('AI Cleaning Plan Generated Successfully', [
                    'total_cents' => $plan['estimated_total_cents'],
                    'services_count' => count($matchedServices),
                    'correlation_id' => $this->correlationId,
                ]);

                return $plan;
            } catch (\Throwable $e) {
                Log::channel('audit')->error('AI Cleaning Plan Generation Failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'correlation_id' => $this->correlationId,
                ]);
                throw $e;
            }
        }

        /**
         * Mock of OpenAI Vision API for photo analysis.
         */
        private function analyzePhotos(array $urls): array
        {
            // Simple mock of AI response
            return [
                'detected_objects' => ['Windows (8 units)', 'Stained grout', 'Hardwood floor', 'Pet hair detected'],
                'area_estimation_sqm' => 64.5,
                'pollution_level' => 'High',
            ];
        }

        /**
         * Matches detected objects with actual services in DB.
         */
        private function matchServices(array $analysis, string $type, ?int $budgetMax): array
        {
            $services = CleaningService::where('is_active', true)
                ->whereIn('category', [$type, 'standard', 'window'])
                ->limit(5)
                ->get();

            $recommendations = [];
            foreach ($services as $service) {
                $cost = $service->price_base_cents * ($analysis['area_estimation_sqm'] / 10);

                if ($budgetMax && $cost > $budgetMax) {
                    continue;
                }

                $recommendations[] = [
                    'id' => $service->id,
                    'name' => $service->name,
                    'estimated_cost_cents' => (int) $cost,
                    'ai_reason' => "Based on pollution level: " . $analysis['pollution_level'],
                ];
            }

            return $recommendations;
        }

        /**
         * Summary calculation logic for the AI Plan.
         */
        private function calculatePlanSummary(array $services, float $areaSqm): array
        {
            $total = array_sum(array_column($services, 'estimated_cost_cents'));
            $duration = count($services) * 45 + ($areaSqm * 2);

            return [
                'total_cents' => $total,
                'duration_min' => (int) $duration,
                'prepayment_cents' => (int) ($total * 0.3),
            ];
        }
}
