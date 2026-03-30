<?php declare(strict_types=1);

namespace App\Domains\Education\LanguageLearning\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LanguageService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private FraudControlService $fraudControl
        ) {}

        /**
         * Регистрация новой школы.
         * @throws \Exception
         */
        public function registerSchool(array $data, string $correlationId): LanguageSchool
        {
            return DB::transaction(function () use ($data, $correlationId) {
                $this->fraudControl->check(['operation' => 'register_school', 'data' => $data]);

                Log::channel('audit')->info('Registering new language school', [
                    'name' => $data['name'],
                    'correlation_id' => $correlationId,
                ]);

                $school = LanguageSchool::create(array_merge($data, [
                    'correlation_id' => $correlationId,
                ]));

                Log::channel('audit')->info('Language school registered successfully', [
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
            return DB::transaction(function () use ($data, $correlationId) {
                $this->fraudControl->check(['operation' => 'create_course', 'data' => $data]);

                $teacher = LanguageTeacher::findOrFail($data['teacher_id']);

                Log::channel('audit')->info('Creating language course', [
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
                    'scheduled_at' => now()->addDays(2),
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
            DB::transaction(function () use ($teacherId, $availability, $correlationId) {
                $teacher = LanguageTeacher::findOrFail($teacherId);

                Log::channel('audit')->info('Updating teacher availability', [
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
