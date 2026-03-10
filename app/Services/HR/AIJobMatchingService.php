<?php

namespace App\Services\HR;

use App\Models\HRJobVacancy;
use App\Models\HRResume;
use App\Models\HRVacancyMatch;
use App\Services\AI\EcosystemAIService;
use Illuminate\Support\Collection;

/**
 * Service for matching Internal HR Vacancies with available talent using Ecosystem AI.
 */
class AIJobMatchingService
{
    public function __construct(
        protected EcosystemAIService $aiService
    ) {}

    /**
     * Finds top matching candidates for a specific vacancy.
     */
    public function getRecommendedCandidates(HRJobVacancy $vacancy, int $limit = 5): Collection
    {
        // 1. Get all resumes (In 2026, we would use vector search via Typesense, here simulated)
        $candidates = HRResume::with('user')->get();

        $scoredCandidates = $candidates->map(function (HRResume $resume) use ($vacancy) {
            $score = $this->calculateSimilarity($vacancy, $resume);
            
            return [
                'user_id' => $resume->user_id,
                'score' => $score,
                'reasons' => $this->generateMatchReasons($vacancy, $resume, $score)
            ];
        })
        ->sortByDesc('score')
        ->take($limit);

        // 2. Persist matches for the vacancy to use in UI
        foreach ($scoredCandidates as $match) {
            HRVacancyMatch::updateOrCreate(
                ['vacancy_id' => $vacancy->id, 'user_id' => $match['user_id']],
                ['match_score' => $match['score'], 'match_reasons' => $match['reasons']]
            );
        }

        return $scoredCandidates;
    }

    /**
     * Simplified similarity calculation for demo purposes.
     * Real implementation would use OpenAI Embeddings/Scout.
     */
    protected function calculateSimilarity(HRJobVacancy $vacancy, HRResume $resume): float
    {
        $vacancySkills = collect($vacancy->skills)->map(fn($s) => strtolower($s));
        $resumeSkills = collect($resume->skills)->map(fn($s) => strtolower($s));
        
        $matchCount = $vacancySkills->intersect($resumeSkills)->count();
        $baseScore = $vacancySkills->count() > 0 ? $matchCount / $vacancySkills->count() : 0.5;
        
        // Final score blend with talent score
        return (float) min(1.0, ($baseScore * 0.7) + ($resume->ai_talent_score * 0.3));
    }

    protected function generateMatchReasons(HRJobVacancy $vacancy, HRResume $resume, float $score): array
    {
        $reasons = [];
        if ($score >= 0.8) $reasons[] = 'Highly compatible skills profile';
        if ($resume->ai_talent_score > 0.9) $reasons[] = 'Top 1% Talent in the ecosystem';
        
        $locationMatch = $vacancy->location_name && str_contains(strtolower($resume->user->address ?? ''), strtolower($vacancy->location_name));
        if ($locationMatch) $reasons[] = 'Local candidate (reduces logistics cost)';

        return $reasons;
    }
}
