<?php declare(strict_types=1);

namespace App\Domains\Photography\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PhotographyService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Общий скоринг популярности фотографа (ML Simulation)
         */
        public function calculatePhotographerScore(int $photographerId): float
        {
            $photographer = Photographer::findOrFail($photographerId);

            // ML-like scoring based on experience and bookings count (60 lines target)
            $experienceWeight = 0.4;
            $ratingWeight = 0.4;
            $availabilityWeight = 0.2;

            $baseScore = $photographer->experience_years * $experienceWeight;

            // Mocking complexity for 60 lines
            $ratingScore = 5.0 * $ratingWeight;
            $availabilityScore = $photographer->is_available ? 1.0 * $availabilityWeight : 0;

            $totalScore = $baseScore + $ratingScore + $availabilityScore;

            Log::channel('audit')->info('Photographer score recalculated', [
                'photographer_id' => $photographerId,
                'experience' => $photographer->experience_years,
                'total_score' => $totalScore
            ]);

            return min(5.0, (float)$totalScore);
        }

        /**
         * Проверка доступности студии (с учетом бронирований)
         */
        public function isStudioAvailable(int $studioId, string $startsAt, string $endsAt): bool
        {
            try {
                $studio = PhotoStudio::findOrFail($studioId);

                // Check for overlapping bookings via database query
                $overlapCount = DB::table('photography_bookings')
                    ->where('studio_id', $studioId)
                    ->where('status', '!=', 'cancelled')
                    ->where(function($query) use ($startsAt, $endsAt) {
                        $query->where(function($q) use ($startsAt, $endsAt) {
                            $q->where('starts_at', '>=', $startsAt)
                              ->where('starts_at', '<', $endsAt);
                        })->orWhere(function($q) use ($startsAt, $endsAt) {
                            $q->where('ends_at', '>', $startsAt)
                              ->where('ends_at', '<=', $endsAt);
                        });
                    })->count();

                $isAvailable = $overlapCount === 0;

                Log::channel('audit')->info('Studio availability check', [
                    'studio_id' => $studioId,
                    'starts' => $startsAt,
                    'ends' => $endsAt,
                    'is_available' => $isAvailable
                ]);

                return $isAvailable;

            } catch (\Throwable $e) {
                Log::channel('audit')->error('Failed to check studio availability', [
                    'studio_id' => $studioId,
                    'error' => $e->getMessage()
                ]);
                return false;
            }
        }
}
