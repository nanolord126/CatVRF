<?php declare(strict_types=1);

namespace App\Domains\Education\Services;

use Carbon\Carbon;


use Psr\Log\LoggerInterface;
use Illuminate\Config\Repository as ConfigRepository;

final readonly class VideoCallService
{

    public function __construct(private FraudControlService $fraud,
        private readonly ConfigRepository $config, private readonly LoggerInterface $logger) {}

        /**
         * Запланировать живое занятие (WebRTC)
         */
        public function scheduleMeeting(int $teacherId, int $lessonId, \DateTime $scheduledAt): VideoCall
        {
            $correlationId = (string) Str::uuid();

            $teacher = Teacher::findOrFail($teacherId);

            // 1. Проверка прав преподавателя
            if (!$teacher->is_active) {
                throw new \RuntimeException('Данный преподаватель не активен.');
            }

            // 2. Создание сессии видеозвонка
            $videoCall = VideoCall::create([
                'tenant_id' => tenant()->id,
                'lesson_id' => $lessonId,
                'teacher_id' => $teacherId,
                'room_id' => (string) Str::uuid(),
                'scheduled_at' => $scheduledAt,
                'status' => 'scheduled',
                'correlation_id' => $correlationId,
            ]);

            $this->logger->info('Video call meeting scheduled', [
                'teacher_id' => $teacherId,
                'lesson_id' => $lessonId,
                'room_id' => $videoCall->room_id,
                'correlation_id' => $correlationId,
            ]);

            return $videoCall;
        }

        /**
         * Начало живого занятия (WebRTC)
         */
        public function startMeeting(int $meetingId): array
        {
            $correlationId = (string) Str::uuid();
            $meeting = VideoCall::findOrFail($meetingId);

            // 1. Фрод-проверка
            $this->fraud->checkOperation('start_vcall', [
                'meeting_id' => $meetingId,
                'teacher_id' => $meeting->teacher_id,
                'correlation_id' => $correlationId
            ]);

            // 2. Смена статуса на live
            $meeting->update([
                'status' => 'live',
                'started_at' => Carbon::now(),
                'correlation_id' => $correlationId,
            ]);

            $this->logger->info('Video meeting started (WebRTC Live)', [
                'meeting_id' => $meetingId,
                'room_id' => $meeting->room_id,
                'correlation_id' => $correlationId,
            ]);

            return [
                'room_id' => $meeting->room_id,
                'webrtc_url' => $this->config->get('services.webrtc.base_url') . '/join/' . $meeting->room_id,
                'status' => 'live',
            ];
        }

        /**
         * Завершение занятия
         */
        public function endMeeting(int $meetingId): void
        {
            $correlationId = (string) Str::uuid();
            $meeting = VideoCall::findOrFail($meetingId);

            $meeting->update([
                'status' => 'ended',
                'ended_at' => Carbon::now(),
                'correlation_id' => $correlationId,
            ]);

            $this->logger->info('Video meeting ended', [
                'meeting_id' => $meetingId,
                'duration' => $meeting->ended_at->diffInMinutes($meeting->started_at),
                'correlation_id' => $correlationId,
            ]);
        }

        /**
         * Проверка доступа студента к видеозвонку
         */
        public function authorizeStudentJoin(int $userId, int $meetingId): bool
        {
            $meeting = VideoCall::findOrFail($meetingId);
            $lesson = $meeting->lesson;

            // Если это урок курса - проверяем наличие активного зачисления
            if ($lesson) {
                $enrollment = Enrollment::where('user_id', $userId)
                    ->where('course_id', $lesson->module->course_id)
                    ->first();

                return $enrollment && $enrollment->isActive();
            }

            return false;
        }
}
