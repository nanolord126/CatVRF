<?php declare(strict_types=1);

namespace App\Services\Consulting;


use Illuminate\Http\Request;
use App\Models\Consulting\Consultant;
use App\Models\Consulting\ConsultingFirm;
use App\Services\FraudControlService;
use Illuminate\Support\Collection;


use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Auth\Guard;

final readonly class ConsultingMatcherService
{

    /**
         * @param string $correlationId Unified audit trace.
         */
        public function __construct(
        private readonly Request $request,
            private readonly FraudControlService $fraud,
            private readonly LogManager $logger,
            private readonly DatabaseManager $db,
            private readonly Guard $guard,
    ) {}

        private function correlationId(): string
        {
            return $this->request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        }

        /**
         * Find best consultants matching specific requirements.
         * Uses skills, budget, and firm reputation.
         */
        public function matchConsultant(array $requirements, int $tenantId): Collection
        {
            $this->logger->channel('audit')->info('Consulting Matcher Initiated', [
                'correlation_id' => $this->correlationId(),
                'tenant_id' => $tenantId,
                'requirements' => $requirements,
            ]);

            $industry = $requirements['industry'] ?? '';
            $maxHourlyRate = $requirements['max_hourly_rate'] ?? 2000000;
            $minExperience = $requirements['min_experience_years'] ?? 5;
            $skills = $requirements['skills'] ?? [];

            return Consultant::query()
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->where('hourly_rate', '<=', $maxHourlyRate)
                ->where('experience_years', '>=', $minExperience)
                ->when($industry, function($query) use ($industry) {
                    return $query->whereHas('firm', function($q) use ($industry) {
                        $q->whereJsonContains('industries', $industry);
                    });
                })
                ->get()
                ->sortByDesc(function(Consultant $consultant) use ($skills) {
                    $score = $consultant->rating;

                    // Skill matching logic (simulating AI vector match)
                    foreach ($skills as $skill) {
                        if ($consultant->isExpertIn($skill)) {
                            $score += 10;
                        }
                    }

                    // Firm prestige bonus
                    if ($consultant->firm?->is_premium) {
                        $score += 15;
                    }

                    return $score;
                })
                ->values();
        }

        /**
         * Create a project proposal using matched experts.
         */
        public function createProjectProposal(int $clientId, int $consultantId, array $projectData): array
        {
            $this->fraud->check((int) $this->guard->id(), 'consulting_create_proposal', $this->request->ip());

            return $this->db->transaction(function() use ($clientId, $consultantId, $projectData) {
                $consultant = Consultant::findOrFail($consultantId);

                $this->logger->channel('audit')->info('Creating Consulting Proposal', [
                    'correlation_id' => $this->correlationId(),
                    'client_id' => $clientId,
                    'consultant_id' => $consultantId,
                ]);

                return [
                    'proposal_id' => (string) Str::uuid(),
                    'consultant' => $consultant->full_name,
                    'estimated_budget' => $projectData['expected_budget'] ?? 0,
                    'suggested_timeline' => '3-6 months',
                    'matching_score' => 95.5,
                    'status' => 'draft',
                    'correlation_id' => $this->correlationId(),
                ];
            });
        }

        /**
         * AI-based pricing suggestion for a new consulting package.
         */
        public function suggestPackagePricing(int $firmId, array $features): int
        {
            $basePrice = 5000000; // 50,000.00 RUB base

            foreach ($features as $feature) {
                 $basePrice += 1000000; // +10,000.00 per feature
            }

            $firm = ConsultingFirm::find($firmId);
            if ($firm?->is_premium) {
                $basePrice = (int) ($basePrice * 1.5);
            }

            return $basePrice;
        }
}
