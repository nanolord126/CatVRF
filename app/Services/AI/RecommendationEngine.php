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
        // Получить все завершённые курсы пользователя из модуля Education
        // Рассчитать процент завершения на основе прогресса студента
        $courses = \DB::table('education_enrollments')
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->join('education_courses', 'education_enrollments.course_id', '=', 'education_courses.id')
            ->select(
                'education_courses.id',
                'education_courses.title',
                'education_courses.category',
                'education_enrollments.progress',
                'education_enrollments.completed_at'
            )
            ->get()
            ->toArray();
        
        return array_map(fn($course) => [
            'id' => $course->id,
            'title' => $course->title,
            'category' => $course->category,
            'completion_rate' => (float) $course->progress,
        ], $courses);
    }
}
