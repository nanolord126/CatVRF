<?php

declare(strict_types=1);

namespace Tests\Feature\LanguageLearning;

use App\Domains\Education\LanguageLearning\Models\LanguageCourse;
use App\Domains\Education\LanguageLearning\Models\LanguageSchool;
use App\Domains\Education\LanguageLearning\Models\LanguageTeacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Тест интеграции LanguageLearning по канону 2026.
 * Проверка API, Маркетплейса и AI-конструктора.
 */
final class LanguageLearningIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private int $tenantId = 101;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['tenant_id' => $this->tenantId]);
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_generate_ai_learning_path(): void
    {
        $correlationId = Str::uuid()->toString();

        $response = $this->postJson(route('api.languages.construct-path'), [
            'language' => 'German',
            'level' => 'A1',
            'goal' => 'Moving to Berlin',
            'weekly_hours' => 10,
            'correlation_id' => $correlationId,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['plan' => ['steps', 'total_price']])
            ->assertJsonPath('correlation_id', $correlationId);
    }

    /** @test */
    public function it_can_enroll_student_to_course(): void
    {
        $school = LanguageSchool::create([
            'tenant_id' => $this->tenantId,
            'name' => 'Test School',
            'uuid' => Str::uuid(),
            'languages' => ['German'],
        ]);

        $teacher = LanguageTeacher::create([
            'tenant_id' => $this->tenantId,
            'school_id' => $school->id,
            'full_name' => 'Hans Schmidt',
            'native_language' => 'German',
            'hourly_rate' => 3000,
            'uuid' => Str::uuid(),
        ]);

        $course = LanguageCourse::create([
            'tenant_id' => $this->tenantId,
            'school_id' => $school->id,
            'teacher_id' => $teacher->id,
            'title' => 'German Intensive',
            'language' => 'German',
            'level_from' => 'A1',
            'level_to' => 'B2',
            'price_total' => 50000,
            'price_per_lesson' => 2000,
            'uuid' => Str::uuid(),
        ]);

        $correlationId = Str::uuid()->toString();

        $response = $this->postJson(route('api.languages.enroll'), [
            'course_id' => $course->id,
            'student_id' => $this->user->id,
            'correlation_id' => $correlationId,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('language_enrollments', [
            'course_id' => $course->id,
            'student_id' => $this->user->id,
            'status' => 'active',
        ]);
    }
}
