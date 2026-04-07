<?php declare(strict_types=1);

namespace App\Domains\Education\LanguageLearning\Services;

use Carbon\Carbon;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class LessonService
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}


    /**
         * Запуск занятия и создание комнаты WebRTC.
         */
        public function startLesson(int $lessonId, string $correlationId): LanguageVideoCall
        {
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
            return $this->db->transaction(function () use ($lessonId, $correlationId) {
                $lesson = LanguageLesson::findOrFail($lessonId);

                if ($lesson->status !== 'scheduled') {
                    throw new \DomainException('Lesson cannot be started from current status: ' . $lesson->status);
                }

                $this->logger->info('Starting language lesson', [
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
                    'started_at' => Carbon::now(),
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
            $this->db->transaction(function () use ($lessonId, $correlationId) {
                $lesson = LanguageLesson::findOrFail($lessonId);
                $call = $lesson->videoCall;

                $this->logger->info('Ending language lesson', [
                    'lesson_id' => $lessonId,
                    'correlation_id' => $correlationId,
                ]);

                $lesson->update([
                    'status' => 'completed',
                    'correlation_id' => $correlationId,
                ]);

                if ($call) {
                    $call->update([
                        'ended_at' => Carbon::now(),
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

            $this->logger->info('Assigning homework for lesson', [
                'lesson_id' => $lessonId,
                'correlation_id' => $correlationId,
            ]);

            $lesson->update([
                'homework' => $homework,
                'correlation_id' => $correlationId,
            ]);
        }
}
