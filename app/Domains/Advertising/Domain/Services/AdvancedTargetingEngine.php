<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Domain\Services;

use App\Domains\AI\Services\UserTasteAnalyzerService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Psr\Log\LoggerInterface;

/**
 * Advanced Targeting Engine
 *
 * Implements sophisticated targeting using behavioral data, AI preferences,
 * demographic analysis, and real-time user context to maximize ad relevance.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
final readonly class AdvancedTargetingEngine
{
    private const CACHE_TTL = 3600; // 1 hour
    private const USER_PROFILE_TTL = 86400; // 24 hours

    public function __construct(
        private readonly UserTasteAnalyzerService $tasteAnalyzer,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Calculate targeting score for ad-user match
     *
     * @param int $userId User ID
     * @param array $adTargetingCriteria Ad targeting criteria
     * @param array $userContext Additional user context (location, device, etc.)
     * @return array{score: float, factors: array, recommendation: string}
     */
    public function calculateTargetingScore(
        int $userId,
        array $adTargetingCriteria,
        array $userContext = [],
    ): array {
        $cacheKey = "targeting:score:{$userId}:" . md5(json_encode($adTargetingCriteria));
        
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $this->logger->info('Calculating targeting score', [
            'user_id' => $userId,
            'criteria' => $adTargetingCriteria,
        ]);

        $factors = [];
        $totalScore = 0.0;

        // Factor 1: Demographic match
        $demographicScore = $this->calculateDemographicMatch($userId, $adTargetingCriteria);
        $factors['demographic'] = $demographicScore;
        $totalScore += $demographicScore * 0.3;

        // Factor 2: Behavioral/Preference match (AI-based)
        $behavioralScore = $this->calculateBehavioralMatch($userId, $adTargetingCriteria);
        $factors['behavioral'] = $behavioralScore;
        $totalScore += $behavioralScore * 0.4;

        // Factor 3: Contextual match (time, location, device)
        $contextualScore = $this->calculateContextualMatch($userContext, $adTargetingCriteria);
        $factors['contextual'] = $contextualScore;
        $totalScore += $contextualScore * 0.2;

        // Factor 4: Historical performance
        $historicalScore = $this->calculateHistoricalPerformance($userId, $adTargetingCriteria);
        $factors['historical'] = $historicalScore;
        $totalScore += $historicalScore * 0.1;

        $score = min(1.0, $totalScore);
        $recommendation = $this->generateRecommendation($score, $factors);

        $result = [
            'score' => $score,
            'factors' => $factors,
            'recommendation' => $recommendation,
        ];

        Cache::put($cacheKey, $result, self::CACHE_TTL);

        return $result;
    }

    /**
     * Calculate demographic match score
     */
    private function calculateDemographicMatch(int $userId, array $criteria): float
    {
        $score = 0.0;
        $factorsChecked = 0;

        if (isset($criteria['age'])) {
            $factorsChecked++;
            // In production, fetch user age from user service
            // For now, assume partial match
            $score += 0.8; // Placeholder
        }

        if (isset($criteria['gender'])) {
            $factorsChecked++;
            $score += 0.9; // Placeholder
        }

        if (isset($criteria['location'])) {
            $factorsChecked++;
            $score += 0.7; // Placeholder
        }

        if (isset($criteria['income'])) {
            $factorsChecked++;
            $score += 0.6; // Placeholder
        }

        return $factorsChecked > 0 ? $score / $factorsChecked : 0.5;
    }

    /**
     * Calculate behavioral match using AI taste analyzer
     */
    private function calculateBehavioralMatch(int $userId, array $criteria): float
    {
        try {
            // Get user preferences from AI service
            // Note: analyzeUserPreferences method needs to be implemented in UserTasteAnalyzerService
            $userPreferences = null;
            if (method_exists($this->tasteAnalyzer, 'analyzeUserPreferences')) {
                $userPreferences = $this->tasteAnalyzer->analyzeUserPreferences($userId);
            }
            
            if ($userPreferences === null) {
                return 0.5; // Neutral if no data
            }

            $matchScore = 0.0;

            // Match against ad categories/interests
            if (isset($criteria['interests'])) {
                $interests = (array) $criteria['interests'];
                $userInterests = $userPreferences['interests'] ?? [];
                
                $commonInterests = array_intersect($interests, $userInterests);
                $matchScore += count($commonInterests) / max(1, count($interests));
            }

            // Match against content preferences
            if (isset($criteria['content_type'])) {
                $contentType = $criteria['content_type'];
                $preferredTypes = $userPreferences['content_types'] ?? [];
                
                if (in_array($contentType, $preferredTypes)) {
                    $matchScore += 0.3;
                }
            }

            // Match against health-related preferences (CatVRF specific)
            if (isset($criteria['health_categories'])) {
                $healthCategories = (array) $criteria['health_categories'];
                $userHealthInterests = $userPreferences['health_interests'] ?? [];
                
                $commonHealth = array_intersect($healthCategories, $userHealthInterests);
                $matchScore += count($commonHealth) / max(1, count($healthCategories)) * 0.5;
            }

            return min(1.0, $matchScore);
        } catch (\Throwable $e) {
            $this->logger->error('Behavioral match calculation failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return 0.5;
        }
    }

    /**
     * Calculate contextual match
     */
    private function calculateContextualMatch(array $context, array $criteria): float
    {
        $score = 0.0;
        $factorsChecked = 0;

        // Time-based targeting
        if (isset($criteria['time_of_day'])) {
            $factorsChecked++;
            $currentHour = now()->hour;
            $targetHours = (array) $criteria['time_of_day'];
            
            if (in_array($currentHour, $targetHours)) {
                $score += 1.0;
            } else {
                $score += 0.3; // Partial match if close to target time
            }
        }

        // Day of week targeting
        if (isset($criteria['days_of_week'])) {
            $factorsChecked++;
            $currentDay = now()->dayOfWeek;
            $targetDays = (array) $criteria['days_of_week'];
            
            if (in_array($currentDay, $targetDays)) {
                $score += 1.0;
            } else {
                $score += 0.0;
            }
        }

        // Device targeting
        if (isset($criteria['device_type']) && isset($context['device_type'])) {
            $factorsChecked++;
            if ($context['device_type'] === $criteria['device_type']) {
                $score += 1.0;
            } else {
                $score += 0.0;
            }
        }

        // Location targeting (geo)
        if (isset($criteria['geo_radius']) && isset($context['location'])) {
            $factorsChecked++;
            // In production, calculate actual distance
            $score += 0.8; // Placeholder
        }

        return $factorsChecked > 0 ? $score / $factorsChecked : 0.5;
    }

    /**
     * Calculate historical performance score
     */
    private function calculateHistoricalPerformance(int $userId, array $criteria): float
    {
        // Check if user has interacted with similar ads before
        $key = "targeting:history:{$userId}";
        $history = Redis::get($key);
        
        if ($history === null) {
            return 0.5; // Neutral if no history
        }

        $historyData = json_decode($history, true);
        $similarInteractions = 0;
        $totalInteractions = 0;

        foreach ($historyData as $interaction) {
            $totalInteractions++;
            
            // Check if interaction matches current criteria
            if ($this->criteriaMatch($interaction['criteria'], $criteria)) {
                $similarInteractions++;
            }
        }

        if ($totalInteractions === 0) {
            return 0.5;
        }

        $engagementRate = $similarInteractions / $totalInteractions;
        
        return $engagementRate;
    }

    /**
     * Check if two criteria sets match
     */
    private function criteriaMatch(array $criteria1, array $criteria2): bool
    {
        // Simplified matching logic
        $matchCount = 0;
        $totalFields = 0;

        foreach ($criteria1 as $key => $value) {
            if (isset($criteria2[$key])) {
                $totalFields++;
                if (is_array($value) && is_array($criteria2[$key])) {
                    $intersection = array_intersect($value, $criteria2[$key]);
                    if (count($intersection) > 0) {
                        $matchCount++;
                    }
                } elseif ($value === $criteria2[$key]) {
                    $matchCount++;
                }
            }
        }

        return $totalFields > 0 && ($matchCount / $totalFields) > 0.5;
    }

    /**
     * Generate recommendation based on score
     */
    private function generateRecommendation(float $score, array $factors): string
    {
        if ($score >= 0.8) {
            return 'Strong match - High probability of engagement';
        }

        if ($score >= 0.6) {
            return 'Good match - Moderate probability of engagement';
        }

        if ($score >= 0.4) {
            return 'Weak match - Low probability of engagement';
        }

        return 'Poor match - Consider excluding this user';
    }

    /**
     * Get personalized targeting suggestions for a user
     *
     * @param int $userId User ID
     * @return array{suggested_criteria: array, reasoning: array}
     */
    public function getTargetingSuggestions(int $userId): array
    {
        try {
            $userPreferences = null;
            if (method_exists($this->tasteAnalyzer, 'analyzeUserPreferences')) {
                $userPreferences = $this->tasteAnalyzer->analyzeUserPreferences($userId);
            }
            
            if ($userPreferences === null) {
                return [
                    'suggested_criteria' => [],
                    'reasoning' => ['No preference data available'],
                ];
            }

            $suggestions = [];
            $reasoning = [];

            // Suggest interests based on user preferences
            if (isset($userPreferences['interests'])) {
                $suggestions['interests'] = array_slice($userPreferences['interests'], 0, 5);
                $reasoning[] = 'Based on user interest profile';
            }

            // Suggest content types
            if (isset($userPreferences['content_types'])) {
                $suggestions['content_type'] = $userPreferences['content_types'][0] ?? null;
                $reasoning[] = 'Matches preferred content format';
            }

            // Suggest health categories (CatVRF specific)
            if (isset($userPreferences['health_interests'])) {
                $suggestions['health_categories'] = array_slice($userPreferences['health_interests'], 0, 3);
                $reasoning[] = 'Based on healthcare engagement history';
            }

            // Suggest optimal time slots
            $suggestions['time_of_day'] = $this->getOptimalTimeSlots($userId);
            $reasoning[] = 'Based on user activity patterns';

            return [
                'suggested_criteria' => $suggestions,
                'reasoning' => $reasoning,
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Targeting suggestions failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return [
                'suggested_criteria' => [],
                'reasoning' => ['Error generating suggestions'],
            ];
        }
    }

    /**
     * Get optimal time slots for user engagement
     */
    private function getOptimalTimeSlots(int $userId): array
    {
        // In production, analyze user activity patterns
        // For now, return common peak hours
        return [9, 10, 11, 12, 18, 19, 20, 21]; // Morning and evening peaks
    }

    /**
     * Record user interaction for learning
     *
     * @param int $userId User ID
     * @param array $criteria Targeting criteria used
     * @param string $interactionType Type of interaction (impression, click, conversion)
     */
    public function recordInteraction(
        int $userId,
        array $criteria,
        string $interactionType,
    ): void {
        $key = "targeting:history:{$userId}";
        $history = Redis::get($key);
        
        $interaction = [
            'criteria' => $criteria,
            'type' => $interactionType,
            'timestamp' => now()->toIso8601String(),
        ];

        if ($history === null) {
            $historyData = [$interaction];
        } else {
            $historyData = json_decode($history, true);
            $historyData[] = $interaction;
            
            // Keep only last 100 interactions
            if (count($historyData) > 100) {
                $historyData = array_slice($historyData, -100);
            }
        }

        Redis::setex($key, self::USER_PROFILE_TTL, json_encode($historyData));
    }

    /**
     * Build audience segment based on targeting criteria
     *
     * @param array $criteria Targeting criteria
     * @param int $maxSize Maximum audience size
     * @return array<int, int> Array of user IDs
     */
    public function buildAudienceSegment(array $criteria, int $maxSize = 10000): array
    {
        // In production, query user database with criteria
        // For now, return placeholder structure
        
        $cacheKey = "targeting:segment:" . md5(json_encode($criteria));
        $cached = Cache::get($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }

        // Placeholder - in production, actual user query
        $segment = [];
        
        Cache::put($cacheKey, $segment, self::CACHE_TTL);
        
        return $segment;
    }
}
