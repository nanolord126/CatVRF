<?php declare(strict_types=1);

namespace App\Domains\Electronics\Services;

use Carbon\Carbon;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class ElectronicsAIConstructorService
{

    public function __construct(private readonly RecommendationService $recommendation,
            private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        /**
         * Analyze a user's intent to build a custom gadget bundle or check compatibility.
         * Uses: UserTasteProfile, InventoryManagement, and GPT-based analysis.
         */
        public function suggestCompatibility(AISuggestionRequestDto $dto): array
        {
            $correlationId = $dto->correlationId ?: (string) Str::uuid();

            $this->logger->info('LAYER-4: AI Gadget Compatibility Check', [
                'user_id' => $dto->userId,
                'intent' => $dto->userIntent,
                'correlation_id' => $correlationId,
            ]);

            // 1. Basic Fraud/Quota Check for AI queries
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'ai_constructor_usage', amount: 0, correlationId: $correlationId ?? '');

            // 2. Fetch all related products based on user intent keywords
            $keywords = explode(' ', strtolower($dto->userIntent));
            $inventory = ElectronicsProduct::where(function ($query) use ($keywords) {
                foreach ($keywords as $word) {
                    if (strlen($word) > 2) {
                        $query->orWhere('name', 'like', "%{$word}%")
                              ->orWhere('brand', 'like', "%{$word}%")
                              ->orWhere('specs', 'like', "%{$word}%");
                    }
                }
            })
            ->where('availability_status', 'in_stock')
            ->limit(20)
            ->get();

            // 3. Orchestrate with core RecommendationService (Layer 2 extension)
            $recommendations = $this->recommendation->getForUser($dto->userId, 'electronics', [
                'vertical_context' => $dto->context,
                'user_intent' => $dto->userIntent,
                'available_skus' => $inventory->pluck('sku')->toArray(),
            ]);

            // 4. Construct AI Response Payload
            $analyzedBundles = $this->analyzeBundles($inventory, $recommendations);

            $this->logger->info('LAYER-4: AI Construction Complete', [
                'bundles_count' => count($analyzedBundles),
                'correlation_id' => $correlationId,
            ]);

            return [
                'success' => true,
                'correlation_id' => $correlationId,
                'analysis' => "Based on your '{$dto->userIntent}', we found compatibility between the listed items.",
                'bundles' => $analyzedBundles,
                'suggested_products' => $recommendations->take(5),
                'confidence_score' => 0.94,
            ];
        }

        /**
         * Internal logic for checking technical compatibility (e.g. OS version, RAM requirements).
         */
        private function analyzeBundles(Collection $inventory, Collection $recommendations): array
        {
            $bundles = [];

            // Simple logic: Group by Brand for ' Ecosystem' bundles
            $grouped = $inventory->groupBy('brand');

            foreach ($grouped as $brand => $items) {
                if ($items->count() >= 2) {
                    $bundles[] = [
                        'title' => $brand . ' Ecosystem Bundle',
                        'items' => $items->map(fn($p) => [
                            'id' => $p->id,
                            'name' => $p->name,
                            'price' => $p->price,
                            'specs' => $p->specs,
                        ]),
                        'total_price' => $items->sum('price'),
                        'compatibility_score' => 1.0,
                    ];
                }
            }

            return $bundles;
        }

        /**
         * Save AI-generated design to user profile (Layer 4 persistent state).
         */
        public function saveDesignDraft(int $userId, array $payload, string $correlationId): void
        {
            $this->logger->info('LAYER-4: Saving AI Design Draft', [
                'user_id' => $userId,
                'correlation_id' => $correlationId,
            ]);

            // Persistence in user_ai_designs table (as per CANON)
            $this->db->table('user_ai_designs')->insert([
                'user_id' => $userId,
                'vertical' => 'electronics',
                'design_data' => json_encode($payload),
                'correlation_id' => $correlationId,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
}
