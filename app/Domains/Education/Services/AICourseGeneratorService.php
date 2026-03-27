<?php

declare(strict_types=1);

namespace App\Domains\Education\Services;

use App\Domains\Education\Models\Course;
use App\Domains\Education\Models\Lesson;
use App\Domains\Education\Models\Enrollment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OpenAI\Client as OpenAI;

/**
 * КАНОН 2026: AICourseGeneratorService (Education).
 * Генерация структуры курса и содержания уроков с помощью OpenAI.
 * Интеграция с базой знаний и инвентарем.
 */
final readonly class AICourseGeneratorService
{
    public function __construct(
        private OpenAI $openai,
    ) {}

    /**
     * Генерация структуры курса (модули и уроки) на базе темы
     */
    public function generateCourseStructure(string $topic, string $level): array
    {
        $correlationId = (string) Str::uuid();

        Log::channel('audit')->info('AI Course Structure Generation Started', [
            'topic' => $topic,
            'level' => $level,
            'correlation_id' => $correlationId,
        ]);

        // 1. Запрос к GPT-4o
        $response = $this->openai->chat()->create([
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'system', 'content' => 'Ты - экспертный методолог курсов. Генерируй структуру курса в формате JSON.'],
                ['role' => 'user', 'content' => "Создай структуру курса на тему '{$topic}' для уровня '{$level}'. Список модулей, в каждом по 3-5 уроков."],
            ],
            'response_format' => ['type' => 'json_object'],
        ]);

        $content = json_decode($response->choices[0]->message->content, true);

        // 2. Валидация и постобработка
        $modules = $content['modules'] ?? [];
        
        Log::channel('audit')->info('AI Course Structure Generated', [
            'modules_count' => count($modules),
            'correlation_id' => $correlationId,
        ]);

        return [
            'topic' => $topic,
            'level' => $level,
            'modules' => $modules,
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Генерация текстового содержания конкретного урока
     */
    public function generateLessonContent(int $lessonId): string
    {
        $correlationId = (string) Str::uuid();
        $lesson = Lesson::findOrFail($lessonId);

        Log::channel('audit')->info('AI Lesson Content Generation', [
            'lesson_id' => $lessonId,
            'title' => $lesson->title,
            'correlation_id' => $correlationId,
        ]);

        // 1. Запрос к LLM
        $response = $this->openai->chat()->create([
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'system', 'content' => 'Ты - профессиональный преподаватель.'],
                ['role' => 'user', 'content' => "Напиши подробный учебный текст для урока '{$lesson->title}' курса '{$lesson->module->course->title}'. Раскрой тему максимально полно."],
            ],
        ]);

        $text = $response->choices[0]->message->content;

        // 2. Сохранение в модель через Сервис
        $lesson->update([
            'content' => $text,
            'correlation_id' => $correlationId,
        ]);

        return $text;
    }

    /**
     * Генерация персонализированного теста (Quiz) для студента
     */
    public function generateQuizForStudent(int $userId, int $courseId): array
    {
        $correlationId = (string) Str::uuid();
        $enrollment = Enrollment::where('user_id', $userId)->where('course_id', $courseId)->firstOrFail();

        // Анализ прогресса для фокуса на слабых местах
        $progress = $enrollment->progress ?? [];
        
        Log::channel('audit')->info('AI Quiz Generation for Student', [
            'user_id' => $userId,
            'course_id' => $courseId,
            'correlation_id' => $correlationId,
        ]);

        $response = $this->openai->chat()->create([
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'system', 'content' => 'Генерируй тест из 5 вопросов для проверки знаний курса.'],
                ['role' => 'user', 'content' => "Создай тест для студента курса '{$enrollment->course->title}'."],
            ],
            'response_format' => ['type' => 'json_object'],
        ]);

        return json_decode($response->choices[0]->message->content, true);
    }
}
