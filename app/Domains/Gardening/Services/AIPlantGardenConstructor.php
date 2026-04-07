<?php declare(strict_types=1);

namespace App\Domains\Gardening\Services;



use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
final readonly class AIPlantGardenConstructor
{

    public function __construct(
            private readonly AIConstructorService $aiConstructor,
            private readonly RecommendationService $recommendation, private readonly Request $request, private readonly LoggerInterface $logger
        ) {}

        /**
         * generateGardenPlan() - Orchestrates the LLM vision and climate data analysis
         * 1. Analyze climate hardiness zones.
         * 2. Analyze seasonality/month.
         * 3. Analyze user's plot layout.
         * 4. Return actionable plant list (with live stock check).
         */
        public function generateGardenPlan(GardenAIRequestDto $dto): array
        {
            $correlationId = $dto->correlationId ?: (string) Str::uuid();

            try {
                // A. Analysis phase (AI Vision + Climate API simulation)
                $analysis = [
                    'hardiness_zone' => $dto->climateZone,
                    'plot_suitability' => $dto->plotType,
                    'current_month' => (int) date('m'),
                    'is_spring' => in_array((int) date('m'), [3, 4, 5]),
                    'light_score' => match ($dto->plotType) {
                        'backyard' => 'full_sun',
                        default => 'full_sun'
                    }
                ];

                // B. Filter matching plants from global catalog (with tenant scoping via model)
                $matchingPlants = GardenPlant::query()
                    ->where('hardiness_zone', '<=', $this->parseZone($dto->climateZone))
                    ->where('light_requirement', $analysis['light_score'])
                    ->with(['product' => function($q) {
                        $q->where('is_published', true)->where('stock_quantity', '>', 0);
                    }])
                    ->get()
                    ->filter(fn ($p) => $p->product !== null);

                // C. Refine with LLM suggestions (simulated for 2026 AI logic)
                $recommendations = $this->aiConstructor->refine(
                    analysis: $analysis,
                    interests: $dto->interests,
                    availableCatalog: $matchingPlants->pluck('product.sku')->toArray(),
                    cid: $correlationId
                );

                // D. Compute monthly maintenance plan
                $plan = $this->computeCarePlan($matchingPlants, (int) date('m'));

                $this->logger->info('Gardening AI Plan Generated', [
                    'user' => $dto->userId,
                    'cid' => $correlationId,
                    'plan_items_count' => count($recommendations['items'] ?? [])
                ]);

                return [
                    'success' => true,
                    'correlation_id' => $correlationId,
                    'climate_analysis' => $analysis,
                    'personalized_plan' => $plan,
                    'suggested_products' => $recommendations['items'] ?? [],
                    'season_warning' => $this->isEarlySpring() ? 'Good time for sowing indoor seeds.' : 'Wait for frost to end.'
                ];

            } catch (\Throwable $e) {
                $this->logger->error('Gardening AI generation failed', [
                    'user' => $dto->userId,
                    'cid' => $correlationId,
                    'error' => $e->getMessage(),
                    'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);

                throw $e;
            }
        }

        /**
         * Internal: map hardiness zone string to numeric score for filtering
         */
        private function parseZone(string $zone): int
        {
            preg_match('/\d+/', $zone, $matches);
            return (int) ($matches[0] ?? 5);
        }

        /**
         * Internal: Build maintenance roadmap based on plant catalog
         */
        private function computeCarePlan(Collection $plants, int $startMonth): array
        {
            $roadmap = [];
            foreach ($plants->take(3) as $plant) {
                $roadmap[] = [
                    'plant_name' => $plant->product->name,
                    'action' => $plant->care_calendar['actions'][$startMonth] ?? 'General watering',
                    'difficulty' => $plant->care_calendar['difficulty'] ?? 'easy'
                ];
            }

            return $roadmap;
        }

        private function isEarlySpring(): bool
        {
            return date('m') === '03';
        }
}
