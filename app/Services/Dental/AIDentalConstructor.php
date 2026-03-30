<?php declare(strict_types=1);

namespace App\Services\Dental;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AIDentalConstructor extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private \App\Services\FraudControlService $fraudControl,
            private PricingService $pricingService,
            private string $correlation_id = ''
        ) {
            $this->correlation_id = empty($correlation_id) ? (string) Str::uuid() : $correlation_id;
        }

        /**
         * Analyze a smile photo and suggest a dental treatment plan.
         */
        public function analyzeSmile(string $photoPath, array $clientWishes, int $budget): array
        {
            return DB::transaction(function () use ($photoPath, $clientWishes, $budget) {
                // 1. Audit Check
                Log::channel('audit')->info('AI Dental Smile Analysis started', [
                    'photo' => $photoPath,
                    'budget' => $budget,
                    'correlation_id' => $this->correlation_id,
                ]);

                // 2. Fraud Check (Medical Privacy/AI Rate Limiting)
                $this->fraudControl->check(['operation' => 'ai_dental_analysis', 'budget' => $budget]);

                // 3. AI Photo Logic (MOCKED for Vision AI API)
                // In Production: Integration with OpenAI / Stable Diffusion
                $analysis = [
                    'tooth_alignment' => 0.65, // 0-1
                    'tooth_color' => 'A3', // Vita scale
                    'gum_health' => 0.85,
                    'detected_issues' => ['Crowding', 'Discoloration', 'Possible Caries'],
                    'confidence_score' => 0.92,
                ];

                // 4. Match Analysis with Services
                $suggestedServices = $this->matchServicesWithAnalysis($analysis, $budget);

                // 5. Aggregate Suggested Treatment Plan
                $totalBudget = $this->pricingService->calculateTotal($suggestedServices);

                return [
                    'analysis' => $analysis,
                    'suggested_plan' => [
                        'services' => $suggestedServices->pluck('name'),
                        'total_estimated_budget' => $totalBudget,
                        'is_within_budget' => $totalBudget <= $budget,
                        'steps_count' => count($suggestedServices),
                    ],
                    'correlation_id' => $this->correlation_id,
                ];
            });
        }

        /**
         * Logic for matching AI detected issues with available dental services.
         */
        private function matchServicesWithAnalysis(array $analysis, int $budget): Collection
        {
            $services = collect();

            // 1. Crowding -> Orthodontics
            if (in_array('Crowding', $analysis['detected_issues'])) {
                $ortho = DentalModel::where('category', 'Orthodontics')->orderBy('base_price')->first();
                if ($ortho) $services->push($ortho);
            }

            // 2. Discoloration -> Hygiene/Whitening
            if (in_array('Discoloration', $analysis['detected_issues']) || $analysis['tooth_color'] > 'A2') {
                $whitening = DentalModel::where('category', 'Therapy')->where('name', 'like', '%Whitening%')->first();
                if ($whitening) $services->push($whitening);
            }

            // 3. Caries -> Therapy
            if (in_array('Possible Caries', $analysis['detected_issues'])) {
                $caries = DentalModel::where('category', 'Therapy')->where('name', 'like', '%Filling%')->first();
                if ($caries) $services->push($caries);
            }

            // Limit results to budget or prioritize high-impact services
            return $services->unique('id');
        }

        /**
         * Generate a visual preview of the "Post-Treatment" smile using AI.
         */
        public function generatePreview(string $originalPhotoPath, array $treatmentServices): string
        {
            // MOCKED AI Photo Generation Logic (DALL-E / Stable Diffusion)
            Log::channel('audit')->info('AI Dental Preview generated', [
                'original' => $originalPhotoPath,
                'services' => count($treatmentServices),
                'correlation_id' => $this->correlation_id,
            ]);

            return "/storage/ai_previews/dental_{$this->correlation_id}.jpg";
        }

        /**
         * Re-calculate dental plan for another budget or set of wishes.
         */
        public function refinePlan(int $planId, array $newWishes, int $newBudget): DentalTreatmentPlan
        {
            return DB::transaction(function () use ($planId, $newWishes, $newBudget) {
                $plan = DentalTreatmentPlan::findOrFail($planId);

                // Re-fetch analysis from metadata or audit
                $analysis = $plan->tags['ai_analysis_snapshot'] ?? [];
                if (empty($analysis)) throw new \RuntimeException('No AI analysis snapshot found for refinement');

                $suggestedServices = $this->matchServicesWithAnalysis($analysis, $newBudget);

                Log::channel('audit')->info('Refining Dental Treatment Plan with AI', [
                    'plan_id' => $planId,
                    'new_budget' => $newBudget,
                    'correlation_id' => $this->correlation_id,
                ]);

                $plan->update([
                    'steps' => $suggestedServices->map(fn($s) => ['name' => $s->name, 'price' => $s->base_price]),
                    'estimated_budget' => $this->pricingService->calculateTotal($suggestedServices),
                    'status' => 'active',
                ]);

                return $plan;
            });
        }
}
