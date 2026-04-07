<?php declare(strict_types=1);

namespace App\Domains\ToysAndGames\Toys\Services;


use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;

final readonly class AIToyConstructor
{
    public function __construct(
        private readonly Request $request, private readonly LoggerInterface $logger) {}


    /**
         * Generate a Recommended Toy Bundle based on Age, Interests, and Budget.
         * Heuristics:
         * - Space: Astronomy, Rockets, Space Lego.
         * - Dinosaurs: Action figures, Paleontology kits.
         * - Coding: Robotics, Math board games.
         */
        public function constructRecommendedOffer(ToyAIRequestDto $dto): array
        {
            $this->logger->info('AI Toy & Game Constructor Invoked', [
                'user_id' => $dto->userId,
                'age_months' => $dto->ageMonths,
                'interests' => $dto->interests,
                'budget' => $dto->budgetLimit,
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);

            // 1. Resolve Age Group match
            $ageGroup = AgeGroup::where('min_age_months', '<=', $dto->ageMonths)
                ->where('max_age_months', '>=', $dto->ageMonths)
                ->first();

            // 2. Fetch Relevant Active Inventory (Price switch B2B/B2C logic included)
            $query = Toy::with(['store', 'category'])
                ->where('is_active', true)
                ->where('stock_quantity', '>', 0);

            if ($ageGroup) {
                $query->where('age_group_id', $ageGroup->id);
            }

            if ($dto->educationalOnly) {
                $query->whereRaw('LOWER(tags::text) LIKE ?', ['%educational%']);
            }

            $toys = $query->get();

            // 3. AI Interest Scoring & Sequencing
            $scoredToys = $toys->map(function (Toy $toy) use ($dto) {
                $score = 0;

                // Heuristic interest matching from tags
                foreach ($dto->interests as $interest) {
                    $interest = strtolower($interest);
                    if (collect($toy->tags)->contains(fn($tag) => str_contains(strtolower($tag), $interest))) {
                        $score += 10;
                    }
                    if (str_contains(strtolower($toy->title), $interest) || str_contains(strtolower($toy->description), $interest)) {
                        $score += 5;
                    }
                }

                // Adjust score by safety certification (priority for certified)
                if ($toy->safety_certification) { $score += 2; }

                // Filter by budget
                $price = $dto->b2bMode ? $toy->price_b2b : $toy->price_b2c;
                if ($price > $dto->budgetLimit) { $score = -100; } // Disqualify if over budget

                return [
                    'toy_id' => $toy->id,
                    'toy_uuid' => $toy->uuid,
                    'title' => $toy->title,
                    'sku' => $toy->sku,
                    'price' => $price,
                    'score' => $score,
                    'gift_wrappable' => $toy->is_gift_wrappable,
                    'stock' => $toy->stock_quantity,
                    'matching_tags' => array_intersect($toy->tags ?? [], $dto->interests)
                ];
            })->filter(fn($item) => $item['score'] > 0)->sortByDesc('score')->values();

            // 4. Transform into Sequential Purchase Options (Roadmap)
            $roadmap = [
                'cid' => (string) Str::uuid(),
                'input_age' => $dto->ageMonths,
                'identified_group' => $ageGroup->name ?? 'Mixed',
                'top_recommendation' => $scoredToys->first() ?? null,
                'alternatives' => $scoredToys->slice(1, 3)->toArray(),
                'total_score' => $scoredToys->sum('score'),
                'message' => $this->generateAIText($dto, $scoredToys->count())
            ];

            $this->logger->info('AI Toy Construction Complete', [
                'cid' => $roadmap['cid'],
                'matches' => count($scoredToys),
                'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
            ]);

            return $roadmap;
        }

        /**
         * Persona-based AI explanatory text.
         */
        private function generateAIText(ToyAIRequestDto $dto, int $matchCount): string
        {
            if ($matchCount === 0) {
                return "Based on your budget of " . ($dto->budgetLimit / 100) . " RUB and specific interests, we might need to adjust the filters to find the perfect gift.";
            }

            $interestFocus = implode(', ', array_slice($dto->interests, 0, 2));
            return "Expert Pick: For a child aged " . round($dto->ageMonths / 12, 1) . " years interested in {$interestFocus}, we recommend starting with our Top Pick for safe and developmental play.";
        }
}
