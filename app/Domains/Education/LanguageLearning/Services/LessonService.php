<?php

declare(strict_types=1);

namespace App\Domains\Education\LanguageLearning\Services;

use App\Domains\Education\LanguageLearning\Models\LanguageLesson;
use App\Domains\Education\LanguageLearning\Models\LanguageVideoCall;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Сервис управления занятиями и WebRTC сессиями.
 */
final readonly class LessonService
{
    /**
     * Запуск занятия и создание комнаты WebRTC.
     */
    public function startLesson(int $lessonId, string $correlationId): LanguageVideoCall
    {
        return DB::transaction(function () use ($lessonId, $correlationId) {
            $lesson = LanguageLesson::findOrFail($lessonId);

            if ($lesson->status !== 'scheduled') {
                throw new \Exception('Lesson cannot be started from current status: ' . $lesson->status);
            }

            Log::channel('audit')->info('Starting language lesson', [
                'lesson_id' => $lessonId,
                'topic' => $lesson->topic,
                'correlation_id' => $correlationId,
            ]);

            $lesson->update([
                'status' => 'active',
                'correlation_id' => $correlationId,
            ]);

            // Генерация комнаты для WebRTC (2026 Fierce WebRTC Engine)
            $call = LanguageVideoCall::create([
                'lesson_id' => $lessonId,
                'room_id' => 'room_' . Str::random(12),
                'provider' => 'internal',
                'started_at' => now(),
                'correlation_id' => $correlationId,
            ]);

            return $call;
        });
    }

    /**
     * Завершение занятия.
     */
    public function endLesson(int $lessonId, string $correlationId): void
    {
        DB::transaction(function () use ($lessonId, $correlationId) {
            $lesson = LanguageLesson::findOrFail($lessonId);
            $call = $lesson->videoCall;

            Log::channel('audit')->info('Ending language lesson', [
                'lesson_id' => $lessonId,
                'correlation_id' => $correlationId,
            ]);

            $lesson->update([
                'status' => 'completed',
                'correlation_id' => $correlationId,
            ]);

            if ($call) {
                $call->update([
                    'ended_at' => now(),
                    'correlation_id' => $correlationId,
                ]);
            }
        });
    }

    /**
     * Назначение домашнего задания.
     */
    public function assignHomework(int $lessonId, string $homework, string $correlationId): void
    {
        $lesson = LanguageLesson::findOrFail($lessonId);

        Log::channel('audit')->info('Assigning homework for lesson', [
            'lesson_id' => $lessonId,
            'correlation_id' => $correlationId,
        ]);

        $lesson->update([
            'homework' => $homework,
            'correlation_id' => $correlationId,
        ]);
    }
}
