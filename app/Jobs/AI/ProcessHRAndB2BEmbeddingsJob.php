<?php

namespace App\Jobs\AI;

use App\Models\HRJobVacancy;
use App\Models\HRResume;
use App\Models\HRVacancyMatch;
use App\Models\B2BManufacturer;
use App\Services\Common\AI\B2BAndHRMatchingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use App\Traits\HasEcosystemTracing;

/**
 * Background Job for processing HR and B2B AI Matching.
 * 2026 Canon: Handles heavy vector processing and cross-referencing.
 */
class ProcessHRAndB2BEmbeddingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HasEcosystemTracing;

    protected string $type;
    protected int $entityId;

    public function __construct(string $type, int $entityId, ?string $correlationId = null)
    {
        $this->type = $type;
        $this->entityId = $entityId;
        $this->setCorrelationId($correlationId);
    }

    public function handle(B2BAndHRMatchingService $matchingService): void
    {
        if ($this->type === 'vacancy') {
            $this->processVacancy($this->entityId, $matchingService);
        } elseif ($this->type === 'manufacturer') {
            $this->processB2B($this->entityId, $matchingService);
        }
    }

    protected function processVacancy(int $vacancyId, B2BAndHRMatchingService $matchingService): void
    {
        $vacancy = HRJobVacancy::find($vacancyId);
        if (!$vacancy) return;

        // Find all active resumes for potential matching
        $resumes = HRResume::where('is_active', true)->limit(100)->get();

        foreach ($resumes as $resume) {
            $matchResult = $matchingService->calculateHRMatch($vacancy, $resume);

            HRVacancyMatch::updateOrCreate(
                ['vacancy_id' => $vacancy->id, 'user_id' => $resume->user_id],
                [
                    'match_score' => $matchResult['total'],
                    'semantic_score' => $matchResult['semantic'],
                    'skill_score' => $matchResult['skill'],
                    'geo_score' => $matchResult['geo'],
                    'match_reasons' => $matchResult['reasons'],
                    'correlation_id' => $this->getCorrelationId()
                ]
            );
        }
    }

    protected function processB2B(int $manufacturerId, B2BAndHRMatchingService $matchingService): void
    {
        $manufacturer = B2BManufacturer::find($manufacturerId);
        if (!$manufacturer) return;

        // In multi-tenancy (schema-per-tenant), this job runs within tenant context
        // and generates recommendations for the current tenant.
        $tenantContext = [
            'latitude' => config('app.tenant_lat'), // Injected via TenancyMiddleware or config
            'longitude' => config('app.tenant_lng'),
        ];

        $recom = $matchingService->calculateB2BRecommendation($manufacturer, $tenantContext);

        // Save to tenant-local recommendations
        DB::table('b2b_recommendations')->updateOrInsert(
            ['manufacturer_id' => $manufacturer->id],
            [
                'match_score' => $recom['total'],
                'reliability_score' => $recom['reliability'],
                'pricing_score' => $recom['pricing'],
                'geo_score' => $recom['geo'],
                'reasons' => json_encode($recom['reasons']),
                'correlation_id' => $this->getCorrelationId(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}
