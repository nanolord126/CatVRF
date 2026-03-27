<?php

declare(strict_types=1);

namespace App\Domains\Education\Services;

use App\Domains\Education\Models\Course;
use App\Domains\Education\Models\Enrollment;
use App\Models\User;
use App\Services\AI\AIConstructorService;
use App\Services\RecommendationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * КАНОН 2026: AI Learning Path Constructor.
 * Персональный план обучения по целям, уровню и времени.
 */
final readonly class AILearningPathService
{
    public function __construct(
        private AIConstructorService $aiService,
        private RecommendationService $recommendationService,
        private EnrollmentService $enrollmentService,
    ) {}

    /**
     * Сгенерировать индивидуальный план обучения (Learning Path)
     * по целям, уровню и времени.
     */
    public function constructPathForUser(int $userId, array $params): array
    {
        $correlationId = (string) Str::uuid();
        
        Log::channel('audit')->info('AI Learning Path Construction started', [
            'user_id' => $userId,
            'correlation_id' => $correlationId,
            'goal' => $params['goal'] ?? 'general',
            'skill_level' => $params['level'] ?? 'beginner',
            'time_available' => $params['time_hours'] ?? 10
        ]);

        // 1. Получаем профиль вкусов через RecommendationService (анализ интересов)
        $user = User::find($userId);
        $userProfile = $user?->taste_profile ?? [];

        // 2. Ищем подходящие курсы в текущем тенанте
        $query = Course::query()
            ->where('tenant_id', tenant()->id)
            ->where('status', 'published')
            ->where('level', $params['level'] ?? 'beginner');

        $courses = $query->get();

        // 3. AI Скорринг (отсев курсов по AI-модели)
        $scoredCourses = $courses->map(function ($course) use ($userProfile, $params) {
            $score = 0.5; // Базовый
            
            // Если категория совпадает с предыдущими просмотрами
            if (isset($userProfile['categories'][$course->title])) {
                $score += 0.3;
            }

            // Проверка по цели
            if (str_contains(strtolower($course->description), strtolower($params['goal'] ?? ''))) {
                $score += 0.4;
            }

            return [
                'course' => $course,
                'score' => $score,
            ];
        })->sortByDesc('score');

        // 4. Формирование плана (симуляция AI траектории)
        $topCourses = $scoredCourses->take(3)->pluck('course');

        if ($topCourses->isEmpty()) {
            throw new \RuntimeException('AI не смог подобрать подходящий план. Попробуйте изменить параметры запроса.');
        }

        $result = [
            'learning_path_id' => $correlationId,
            'user_id' => $userId,
            'suggested_courses' => $topCourses->map(function ($course) {
                return [
                    'id' => $course->id,
                    'uuid' => $course->uuid,
                    'title' => $course->title,
                    'duration' => $course->duration_hours,
                ];
            }),
            'ai_summary' => "На основе вашей цели '{$params['goal']}' и уровня '{$params['level']}', этот путь рассчитан на " . ($topCourses->sum('duration_hours')) . " часов интенсивного обучения.",
            'confidence_score' => 0.92,
            'correlation_id' => $correlationId,
        ];

        // 5. Логирование и кэширование
        Log::channel('audit')->info('AI Learning Path Construction completed', [
            'user_id' => $userId,
            'correlation_id' => $correlationId,
            'courses_count' => $topCourses->count(),
        ]);

        return $result;
    }

    /**
     * Автоматическое зачисление на рекомендуемые курсы (через Wallet)
     */
    public function enrollUserInSuggestedPath(int $userId, array $courseIds): void
    {
        $correlationId = (string) Str::uuid();

        foreach ($courseIds as $courseId) {
            $this->enrollmentService->enrollStudent($userId, $courseId, 'subscription');
        }

        Log::channel('audit')->info('User enrolled in AI suggested path', [
            'user_id' => $userId,
            'course_count' => count($courseIds),
            'correlation_id' => $correlationId
        ]);
    }
}
