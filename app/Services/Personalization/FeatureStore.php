<?php

declare(strict_types=1);

namespace App\Services\Personalization;

use Illuminate\Support\Collection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Cache\CacheManager;

final readonly class FeatureStore
{
    private const CACHE_TTL_HOURS = 6;

    private const ACTION_COUNTER_TTL_SECONDS = 86400; // 24 hours

    public function __construct(
        private readonly CacheManager $cache,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * Extract features for a user
     */
    public function extractForUser(\App\Models\User $user, string $vertical): array
    {
        $cacheKey = "features:user:{$user->id}:{$vertical}";

        return Cache::remember($cacheKey, self::CACHE_TTL_HOURS * 3600, function () use ($user, $vertical) {
            $features = [
                'user_id' => $user->id,
                'vertical' => $vertical,
                'demographic' => $this->extractDemographicFeatures($user),
                'behavioral' => $this->extractBehavioralFeatures($user, $vertical),
                'historical' => $this->extractHistoricalFeatures($user, $vertical),
                'preferences' => $this->extractPreferenceFeatures($user, $vertical),
            ];

            // Add allergy embeddings (anonymized)
            $features['allergy_embeddings'] = $this->extractAllergyEmbeddings($user);

            return $features;
        });
    }

    /**
     * Extract features for a pet
     */
    public function extractForPet(object $pet, string $vertical): array
    {
        $cacheKey = "features:pet:{$pet->id}:{$vertical}";

        return Cache::remember($cacheKey, self::CACHE_TTL_HOURS * 3600, function () use ($pet, $vertical) {
            $features = [
                'pet_id' => $pet->id,
                'vertical' => $vertical,
                'demographic' => $this->extractPetDemographicFeatures($pet),
                'behavioral' => $this->extractPetBehavioralFeatures($pet, $vertical),
                'historical' => $this->extractPetHistoricalFeatures($pet, $vertical),
                'medical' => $this->extractMedicalFeatures($pet),
            ];

            return $features;
        });
    }

    /**
     * Extract features for an item
     */
    public function extractForItem(object $item): array
    {
        $cacheKey = "features:item:{$item->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL_HOURS * 3600, function () use ($item) {
            return [
                'item_id' => $item->id,
                'type' => get_class($item),
                'content' => $this->extractContentFeatures($item),
                'collaborative' => $this->extractCollaborativeFeatures($item),
                'contextual' => $this->extractContextualFeatures($item),
            ];
        });
    }

    /**
     * Get trending items
     */
    public function getTrendingItems(string $vertical, int $limit): Collection
    {
        return $this->getEntityTypeForVertical($vertical)
            ->where('is_active', true)
            ->where('created_at', '>=', now()->subDays(7))
            ->withCount(['bookings' => function ($query) {
                $query->where('created_at', '>=', now()->subDays(7));
            }])
            ->orderByDesc('bookings_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Get popular items
     */
    public function getPopularItems(string $vertical, int $limit): Collection
    {
        return $this->getEntityTypeForVertical($vertical)
            ->where('is_active', true)
            ->withCount('bookings')
            ->orderByDesc('bookings_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Update user features based on action
     */
    public function updateUserFeatures(int $userId, string $action): void
    {
        // Invalidate cache
        Cache::forget("features:user:{$userId}:beauty");
        Cache::forget("features:user:{$userId}:grooming");
        Cache::forget("features:user:{$userId}:food");
        Cache::forget("features:user:{$userId}:fitness");

        // Update real-time counters in Redis
        $key = "user:{$userId}:actions:{$action}";
        Cache::increment($key);
        Cache::expire($key, self::ACTION_COUNTER_TTL_SECONDS); // 24 hours
    }

    private function extractDemographicFeatures(\App\Models\User $user): array
    {
        return [
            'age' => $this->calculateAge($user->birth_date ?? null),
            'gender' => $user->gender ?? 'unknown',
            'city' => $user->city ?? 'unknown',
            'region' => $user->region ?? 'unknown',
            'membership_tier' => $user->membership_tier ?? 'standard',
            'registration_days' => $user->created_at->diffInDays(now()),
        ];
    }

    private function extractBehavioralFeatures(\App\Models\User $user, string $vertical): array
    {
        $last30Days = now()->subDays(30);

        $bookings = $this->getUserBookings($user->id, $last30Days, $vertical);
        $totalSpent = $bookings->sum('total_price');

        return [
            'visits_last_30_days' => $bookings->count(),
            'avg_session_value' => $bookings->count() > 0 ? $totalSpent / $bookings->count() : 0,
            'total_spent_last_30_days' => $totalSpent,
            'preferred_time_slot' => $this->getPreferredTimeSlot($bookings),
            'preferred_day' => $this->getPreferredDay($bookings),
            'conversion_rate' => $this->calculateConversionRate($user, $vertical),
        ];
    }

    private function extractHistoricalFeatures(\App\Models\User $user, string $vertical): array
    {
        $last90Days = now()->subDays(90);
        $bookings = $this->getUserBookings($user->id, $last90Days, $vertical);

        return [
            'total_visits' => $bookings->count(),
            'avg_rating_given' => $bookings->avg('rating') ?? 0,
            'cancellation_rate' => $this->calculateCancellationRate($bookings),
            'no_show_rate' => $this->calculateNoShowRate($bookings),
            'repeat_customer' => $bookings->count() > 3,
            'loyalty_points' => $user->loyalty_points ?? 0,
        ];
    }

    private function extractPreferenceFeatures(\App\Models\User $user, string $vertical): array
    {
        $preferences = [];

        // Extract from user's favorite categories
        $favoriteCategories = $this->db->table('user_category_preferences')
            ->where('user_id', $user->id)
            ->where('vertical', $vertical)
            ->orderByDesc('score')
            ->limit(5)
            ->get();

        $preferences['favorite_categories'] = $favoriteCategories->pluck('category_id')->toArray();

        // Extract from user's tags
        $userTags = $this->db->table('user_tags')
            ->where('user_id', $user->id)
            ->pluck('tag')
            ->toArray();

        $preferences['tags'] = $userTags;

        return $preferences;
    }

    private function extractAllergyEmbeddings(\App\Models\User $user): array
    {
        // Anonymized allergy embeddings for ML
        $allergies = $this->db->table('allergies')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->pluck('name')
            ->toArray();

        // Convert to embeddings (simplified - in production use real embeddings)
        return array_map(fn ($allergy) => $this->hashAllergyName($allergy), $allergies);
    }

    private function extractPetDemographicFeatures(object $pet): array
    {
        return [
            'species' => $pet->species ?? 'unknown',
            'breed' => $pet->breed ?? 'mixed',
            'age_years' => $pet->age ?? 0,
            'weight_kg' => $pet->weight ?? 0,
            'gender' => $pet->gender ?? 'unknown',
            'is_neutered' => $pet->is_neutered ?? false,
        ];
    }

    private function extractPetBehavioralFeatures(object $pet, string $vertical): array
    {
        $last30Days = now()->subDays(30);
        $visits = $this->db->table('appointments')
            ->where('pet_id', $pet->id)
            ->where('created_at', '>=', $last30Days)
            ->get();

        return [
            'visits_last_30_days' => $visits->count(),
            'avg_stress_level' => $visits->avg('stress_level') ?? 0,
            'behavior_score' => $visits->avg('behavior_score') ?? 5,
        ];
    }

    private function extractPetHistoricalFeatures(object $pet, string $vertical): array
    {
        $visits = $this->db->table('appointments')
            ->where('pet_id', $pet->id)
            ->get();

        return [
            'total_visits' => $visits->count(),
            'last_visit_days_ago' => $visits->max('created_at')
                ? now()->diffInDays($visits->max('created_at'))
                : 999,
            'vaccination_status' => $pet->vaccination_status ?? 'unknown',
        ];
    }

    private function extractMedicalFeatures(object $pet): array
    {
        return [
            'has_chronic_conditions' => $this->db->table('pet_chronic_conditions')
                ->where('pet_id', $pet->id)
                ->exists(),
            'medication_count' => $this->db->table('pet_medications')
                ->where('pet_id', $pet->id)
                ->where('is_active', true)
                ->count(),
            'allergy_count' => $this->db->table('allergies')
                ->where('pet_id', $pet->id)
                ->where('is_active', true)
                ->count(),
        ];
    }

    private function extractContentFeatures(object $item): array
    {
        $features = [
            'category_id' => $item->category_id ?? null,
            'price' => $item->price ?? 0,
            'duration_minutes' => $item->duration ?? 0,
        ];

        // Add composition embeddings if available
        if (method_exists($item, 'composition') && $item->composition) {
            $features['composition_embeddings'] = $this->extractCompositionEmbeddings($item->composition);
        }

        return $features;
    }

    private function extractCollaborativeFeatures(object $item): array
    {
        return [
            'popularity_score' => $this->db->table('bookings')
                ->where('bookable_type', get_class($item))
                ->where('bookable_id', $item->id)
                ->count(),
            'avg_rating' => $this->db->table('reviews')
                ->where('reviewable_type', get_class($item))
                ->where('reviewable_id', $item->id)
                ->avg('rating') ?? 0,
            'conversion_rate' => $this->calculateItemConversionRate($item),
        ];
    }

    private function extractContextualFeatures(object $item): array
    {
        return [
            'seasonal_popularity' => $this->getSeasonalPopularity($item),
            'time_of_day_popularity' => $this->getTimeOfDayPopularity($item),
            'price_sensitivity' => $this->getPriceSensitivity($item),
        ];
    }

    private function extractCompositionEmbeddings(object $composition): array
    {
        // Simplified embedding - in production use real text embeddings
        return array_map(
            fn ($ingredient) => $this->hashIngredient($ingredient),
            $composition->ingredients ?? []
        );
    }

    private function getUserBookings(int $userId, \Carbon\Carbon $since, string $vertical): Collection
    {
        return $this->db->table('bookings')
            ->where('user_id', $userId)
            ->where('vertical', $vertical)
            ->where('created_at', '>=', $since)
            ->get();
    }

    private function calculateAge(?string $birthDate): int
    {
        if (!$birthDate) {
            return 0;
        }

        return \Carbon\Carbon::parse($birthDate)->age;
    }

    private function getPreferredTimeSlot(Collection $bookings): string
    {
        if ($bookings->isEmpty()) {
            return 'unknown';
        }

        $timeSlots = $bookings->pluck('start_time')->map(function ($time) {
            $hour = \Carbon\Carbon::parse($time)->hour;
            return match (true) {
                $hour < 12 => 'morning',
                $hour < 17 => 'afternoon',
                default => 'evening',
            };
        });

        return $timeSlots->mode()->first() ?? 'unknown';
    }

    private function getPreferredDay(Collection $bookings): string
    {
        if ($bookings->isEmpty()) {
            return 'unknown';
        }

        $days = $bookings->pluck('start_time')->map(function ($time) {
            return \Carbon\Carbon::parse($time)->dayName;
        });

        return $days->mode()->first() ?? 'unknown';
    }

    private function calculateConversionRate(\App\Models\User $user, string $vertical): float
    {
        $views = $this->db->table('item_views')
            ->where('user_id', $user->id)
            ->where('vertical', $vertical)
            ->count();

        if ($views === 0) {
            return 0.0;
        }

        $bookings = $this->getUserBookings($user->id, now()->subDays(30), $vertical)->count();

        return $bookings / $views;
    }

    private function calculateCancellationRate(Collection $bookings): float
    {
        if ($bookings->isEmpty()) {
            return 0.0;
        }

        $cancelled = $bookings->where('status', 'cancelled')->count();

        return $cancelled / $bookings->count();
    }

    private function calculateNoShowRate(Collection $bookings): float
    {
        if ($bookings->isEmpty()) {
            return 0.0;
        }

        $noShows = $bookings->where('status', 'no_show')->count();

        return $noShows / $bookings->count();
    }

    private function calculateItemConversionRate(object $item): float
    {
        $views = $this->db->table('item_views')
            ->where('item_type', get_class($item))
            ->where('item_id', $item->id)
            ->count();

        if ($views === 0) {
            return 0.0;
        }

        $bookings = $this->db->table('bookings')
            ->where('bookable_type', get_class($item))
            ->where('bookable_id', $item->id)
            ->count();

        return $bookings / $views;
    }

    private function getSeasonalPopularity(object $item): float
    {
        $currentSeason = $this->getCurrentSeason();

        return $this->db->table('bookings')
            ->where('bookable_type', get_class($item))
            ->where('bookable_id', $item->id)
            ->where('season', $currentSeason)
            ->count() / max(1, $this->db->table('bookings')
                ->where('bookable_type', get_class($item))
                ->where('bookable_id', $item->id)
                ->count());
    }

    private function getTimeOfDayPopularity(object $item): array
    {
        // Return popularity by time of day
        return [
            'morning' => 0.3,
            'afternoon' => 0.5,
            'evening' => 0.2,
        ];
    }

    private function getPriceSensitivity(object $item): float
    {
        // Calculate how price-sensitive users are for this item
        return 0.5;
    }

    private function getCurrentSeason(): string
    {
        $month = now()->month;

        return match (true) {
            $month >= 3 && $month <= 5 => 'spring',
            $month >= 6 && $month <= 8 => 'summer',
            $month >= 9 && $month <= 11 => 'autumn',
            default => 'winter',
        };
    }

    private function getEntityTypeForVertical(string $vertical)
    {
        return match ($vertical) {
            'beauty' => \Modules\BeautyMasters\Domain\Entities\Service::class,
            'grooming' => \Modules\VetGrooming\Domain\Entities\Service::class,
            'food' => \Modules\Restaurant\Domain\Entities\Product::class,
            'fitness' => \Modules\Fitness\Domain\Entities\Service::class,
            default => throw new \InvalidArgumentException("Unknown vertical: {$vertical}"),
        };
    }

    private function hashAllergyName(string $allergy): string
    {
        return md5(strtolower($allergy));
    }

    private function hashIngredient(string $ingredient): string
    {
        return md5(strtolower($ingredient));
    }
}
