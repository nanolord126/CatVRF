<?php declare(strict_types=1);

namespace App\Domains\Education\LanguageLearning\Services;

use Carbon\Carbon;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class LanguageService
{

    public function __construct(private FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        /**
         * Регистрация новой школы.
         * @throws \RuntimeException
         */
        public function registerSchool(array $data, string $correlationId): LanguageSchool
        {
            return $this->db->transaction(function () use ($data, $correlationId) {
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'register_school', amount: 0, correlationId: $correlationId ?? '');

                $this->logger->info('Registering new language school', [
                    'name' => $data['name'],
                    'correlation_id' => $correlationId,
                ]);

                $school = LanguageSchool::create(array_merge($data, [
                    'correlation_id' => $correlationId,
                ]));

                $this->logger->info('Language school registered successfully', [
                    'school_id' => $school->id,
                    'correlation_id' => $correlationId,
                ]);

                return $school;
            });
        }

        /**
         * Создание нового курса премодерированного преподавателя.
         */
        public function createCourse(array $data, string $correlationId): LanguageCourse
        {
            return $this->db->transaction(function () use ($data, $correlationId) {
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'create_course', amount: 0, correlationId: $correlationId ?? '');

                $teacher = LanguageTeacher::findOrFail($data['teacher_id']);

                $this->logger->info('Creating language course', [
                    'title' => $data['title'],
                    'teacher' => $teacher->full_name,
                    'correlation_id' => $correlationId,
                ]);

                $course = LanguageCourse::create(array_merge($data, [
                    'correlation_id' => $correlationId,
                    'uuid' => (string) Str::uuid(),
                ]));

                // Автоматическое создание первого вводного урока
                $course->lessons()->create([
                    'topic' => 'Introductory Lesson: Placement Test',
                    'scheduled_at' => Carbon::now()->addDays(2),
                    'duration_minutes' => 45,
                    'status' => 'scheduled',
                    'correlation_id' => $correlationId,
                ]);

                return $course;
            });
        }

        /**
         * Поиск активных учителей в тенанте с фильтрацией по языку.
         */
        public function getActiveTeachers(string $language = null): Collection
        {
            $query = LanguageTeacher::with('school');

            if ($language) {
                $query->whereJsonContains('teaching_languages', $language);
            }

            return $query->where('rating', '>=', 4.0)->get();
        }

        /**
         * Обновление расписания учителя.
         */
        public function updateAvailability(int $teacherId, array $availability, string $correlationId): void
        {
            $this->db->transaction(function () use ($teacherId, $availability, $correlationId) {
                $teacher = LanguageTeacher::findOrFail($teacherId);

                $this->logger->info('Updating teacher availability', [
                    'teacher_id' => $teacherId,
                    'correlation_id' => $correlationId,
                ]);

                $teacher->update([
                    'availability' => $availability,
                    'correlation_id' => $correlationId,
                ]);
            });
        }
}
