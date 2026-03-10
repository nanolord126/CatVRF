<?php

namespace App\Services\AI;

use App\Models\Course;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Collection;

class RecommendationEngine
{
    /**
     * AI-driven recommendations based on user history and 2026 data.
     */
    public function getPersonalizedSuggestions(User $user, string $type): Collection
    {
        return match($type) {
            'education' => Course::whereNotIn('id', $this->getUserEnrolledCourses($user))
                ->limit(5)
                ->get(),
            'events' => Event::where('status', 'published')
                ->where('start_at', '>', now())
                ->limit(3)
                ->get(),
            default => collect([])
        };
    }

    private function getUserEnrolledCourses(User $user): array
    {
        // Placeholder for real logic from education module progress
        return [];
    }
}
