<?php declare(strict_types=1);

namespace App\Domains\Education\Tests;

use Tests\TestCase;

final class EducationVerticalIntegrationTest extends TestCase
{

    use RefreshDatabase;

        protected function setUp(): void
        {
            parent::setUp();
            // Mocking WalletService to avoid real balance changes
            $this->instance(WalletService::class, Mockery::mock(WalletService::class, function ($mock) {
                $mock->shouldReceive('debit')->andReturn(true);
                $mock->shouldReceive('credit')->andReturn(true);
            }));
        }

        /**
         * Тест создания курса и зачисления студента
         */
        public function test_student_can_enroll_to_course(): void
        {
            $correlationId = (string) Str::uuid();

            // 1. Создание преподавателя и курса
            $teacher = Teacher::create([
                'tenant_id' => 1,
                'user_id' => User::factory()->create()->id,
                'full_name' => 'Иван Преподаватель',
                'is_active' => true,
                'correlation_id' => $correlationId,
            ]);

            $course = Course::create([
                'tenant_id' => 1,
                'teacher_id' => $teacher->id,
                'title' => 'Основы Laravel 2026',
                'description' => 'Курс по канонам разработки',
                'price_kopecks' => 100000, // 1000 RUB
                'status' => 'published',
                'correlation_id' => $correlationId,
            ]);

            // 2. Создание студента
            $student = User::factory()->create();

            // 3. Зачисление через сервис
            $service = app(EnrollmentService::class);
            $enrollment = $service->enrollStudent($student->id, $course->id, 'b2c');

            // 4. Проверки БД
            $this->assertDatabaseHas('education_enrollments', [
                'user_id' => $student->id,
                'course_id' => $course->id,
                'status' => 'active',
                'type' => 'b2c',
            ]);

            // 5. Проверка Correlation ID в модели
            $this->assertEquals($correlationId, $course->correlation_id);
        }

        /**
         * Тест: Студент не может зачислиться на неопубликованный курс
         */
        public function test_cannot_enroll_to_non_published_course(): void
        {
            $teacher = Teacher::create([
                'tenant_id' => 1,
                'user_id' => User::factory()->create()->id,
                'full_name' => 'Иван Преподаватель',
                'is_active' => true,
            ]);

            $course = Course::create([
                'tenant_id' => 1,
                'teacher_id' => $teacher->id,
                'title' => 'Скрытый курс',
                'price_kopecks' => 1000,
                'status' => 'draft', // Черновик
            ]);

            $student = User::factory()->create();
            $service = app(EnrollmentService::class);

            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('Курс не доступен для зачисления');

            $service->enrollStudent($student->id, $course->id, 'b2c');
        }
}
