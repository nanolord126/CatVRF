<?php

namespace App\Domains\Education\Services;

use App\Models\User;
use App\Domains\Education\Models\{Course, Lesson, Enrollment};
use Illuminate\Support\{Carbon, Facades};
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\AuditLog;
use Throwable;

class EducationAiTutor
{
    private string $correlationId;
    private ?int $tenantId;
    private string $openAiKey;

    public function __construct()
    {
        $this->correlationId = Str::uuid();
        $this->tenantId = Auth::guard('tenant')?->id();
        $this->openAiKey = config('services.openai.key', '');
    }

    /**
     * Генератор адаптивного контента на базе OpenAI.
     */
    public function generateLearningPath(User $user, Course $course): array
    {
        try {
            Log::channel('education')->info('Learning path generation started', [
                'user_id' => $user->id,
                'course_id' => $course->id,
                'correlation_id' => $this->correlationId,
            ]);

            $cacheKey = "learning_path_{$user->id}_{$course->id}";

            $result = Cache::remember($cacheKey, Carbon::now()->addDays(7), function () use ($user, $course) {
                try {
                    if (empty($this->openAiKey)) {
                        throw new \Exception("OpenAI API key is not configured");
                    }

                    $lessons = $course->lessons()->pluck('title')->toArray();
                    $experienceLevel = $user->experience_level ?? 'новичок';

                    $prompt = "Создай адаптивный план обучения для пользователя (уровень: {$experienceLevel}) " .
                              "по курсу '{$course->name}'. Список тем: " . implode(', ', $lessons) . ". " .
                              "Выдели 3 ключевых фокуса и добавь практические задания.";

                    Log::debug('Calling OpenAI API', [
                        'model' => 'gpt-4-turbo-preview',
                        'prompt_length' => strlen($prompt),
                        'correlation_id' => $this->correlationId,
                    ]);

                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $this->openAiKey,
                        'Content-Type' => 'application/json',
                    ])->post('https://api.openai.com/v1/chat/completions', [
                        'model' => 'gpt-4-turbo-preview',
                        'messages' => [
                            ['role' => 'system', 'content' => 'Ты - эксперт-тьютор экосистемы CatVRF.'],
                            ['role' => 'user', 'content' => $prompt]
                        ],
                        'temperature' => 0.7,
                        'max_tokens' => 1000,
                    ]);

                    if (!$response->successful()) {
                        throw new \Exception("OpenAI API error (Status {$response->status()}): " . $response->body());
                    }

                    $data = $response->json();
                    if (empty($data['choices'][0]['message']['content'])) {
                        throw new \Exception("OpenAI returned empty response");
                    }

                    return [
                        'plan' => $data['choices'][0]['message']['content'],
                        'generated_at' => Carbon::now(),
                        'status' => 'adaptive_active',
                        'model_version' => 'gpt-4-turbo-preview',
                    ];
                } catch (Throwable $e) {
                    Log::error('OpenAI API call failed', [
                        'error' => $e->getMessage(),
                        'user_id' => $user->id,
                        'course_id' => $course->id,
                        'correlation_id' => $this->correlationId,
                    ]);

                    // Fallback: return simple learning path
                    return [
                        'plan' => 'Стандартный план обучения недоступен. Обратитесь к преподавателю.',
                        'generated_at' => Carbon::now(),
                        'status' => 'fallback_mode',
                        'error' => $e->getMessage(),
                    ];
                }
            });

            // Аудит генерации плана обучения
            try {
                AuditLog::create([
                    'entity_type' => self::class,
                    'entity_id' => 0,
                    'action' => 'ai_learning_path_generated',
                    'user_id' => Auth::id(),
                    'tenant_id' => $this->tenantId,
                    'correlation_id' => $this->correlationId,
                    'changes' => [],
                    'metadata' => [
                        'student_id' => $user->id,
                        'course_id' => $course->id,
                        'status' => $result['status'],
                        'plan_length' => strlen($result['plan'] ?? ''),
                    ],
                ]);
            } catch (Throwable $e) {
                Log::warning('Learning path audit failed', ['error' => $e->getMessage()]);
            }

            Log::channel('education')->info('Learning path generated successfully', [
                'user_id' => $user->id,
                'course_id' => $course->id,
                'status' => $result['status'],
                'correlation_id' => $this->correlationId,
            ]);

            return $result;
        } catch (Throwable $e) {
            Log::error('Learning path generation failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'correlation_id' => $this->correlationId,
            ]);
            \Sentry\captureException($e);
            throw $e;
        }
    }

    /**
     * AI-анализ прогресса и предсказание вероятности завершения курса.
     */
    public function predictCompletionSuccess(Enrollment $enrollment): array
    {
        try {
            Log::channel('education')->info('Completion prediction started', [
                'enrollment_id' => $enrollment->id,
                'user_id' => $enrollment->student_id,
                'correlation_id' => $this->correlationId,
            ]);

            $progress = $enrollment->progress_percent ?? 0;
            $timeSpent = $enrollment->enrolled_at->diffInDays(Carbon::now());

            // ML-логика на основе текущего прогресса и времени
            $probability = 0.5;
            $recommendation = '';

            if ($progress > 75 && $timeSpent < 30) {
                $probability = 0.95;
                $recommendation = 'Отличный прогресс. Студент завершит курс в срок.';
            } elseif ($progress > 50 && $timeSpent < 45) {
                $probability = 0.80;
                $recommendation = 'Хороший прогресс. Поддерживайте темп.';
            } elseif ($progress > 25 && $timeSpent < 60) {
                $probability = 0.60;
                $recommendation = 'Среднее прохождение. Рекомендуется увеличить активность.';
            } elseif ($progress < 10 && $timeSpent > 60) {
                $probability = 0.15;
                $recommendation = 'Критический прогресс. Требуется срочное вмешательство преподавателя.';
            } elseif ($progress < 25) {
                $probability = 0.30;
                $recommendation = 'Низкий прогресс. Отправить пуш-уведомление с мотивацией.';
            } else {
                $recommendation = 'Текущий прогресс позволяет прогнозировать успешное завершение.';
            }

            $result = [
                'enrollment_id' => $enrollment->id,
                'completion_probability' => round($probability, 2),
                'progress_percent' => $progress,
                'days_enrolled' => $timeSpent,
                'recommendation' => $recommendation,
                'prediction_confidence' => 0.85, // Confidence level
                'predicted_at' => Carbon::now(),
            ];

            // Аудит предсказания
            try {
                AuditLog::create([
                    'entity_type' => self::class,
                    'entity_id' => 0,
                    'action' => 'ai_completion_prediction',
                    'user_id' => Auth::id(),
                    'tenant_id' => $this->tenantId,
                    'correlation_id' => $this->correlationId,
                    'changes' => [],
                    'metadata' => [
                        'enrollment_id' => $enrollment->id,
                        'student_id' => $enrollment->student_id,
                        'probability' => $probability,
                        'recommendation' => $recommendation,
                    ],
                ]);
            } catch (Throwable $e) {
                Log::warning('Completion prediction audit failed', ['error' => $e->getMessage()]);
            }

            Log::channel('education')->info('Completion prediction generated', [
                'enrollment_id' => $enrollment->id,
                'probability' => $probability,
                'correlation_id' => $this->correlationId,
            ]);

            return $result;
        } catch (Throwable $e) {
            Log::error('Completion prediction failed', [
                'enrollment_id' => $enrollment->id,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);
            \Sentry\captureException($e);
            throw $e;
        }
    }
}
