<?php declare(strict_types=1);

namespace Tests\Feature\Domains\Education;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CourseEnrollmentApiTest extends TestCase
{
    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    public function test_enroll_unauthorized(): void
    {
        $response = $this->postJson('/api/v1/education/enrollments');
        $response->assertStatus(401);
    }

    public function test_enroll_b2c_success(): void
    {
        DB::table('courses')->insert([
            'id' => 1,
            'uuid' => 'course-uuid-1',
            'tenant_id' => tenant()->id,
            'teacher_id' => 1,
            'title' => 'Test Course',
            'price_kopecks' => 100000,
            'corporate_price_kopecks' => 80000,
            'status' => 'published',
        ]);

        DB::table('education_course_modules')->insert([
            'id' => 1,
            'course_id' => 1,
            'title' => 'Module 1',
            'order' => 1,
        ]);

        $response = $this->withToken($this->token)
            ->postJson('/api/v1/education/enrollments', [
                'user_id' => $this->user->id,
                'course_id' => 1,
            ], [
                'X-Correlation-ID' => 'test-correlation-123',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'mode' => 'b2c',
                'total_price_rub' => 1000.0,
                'marketplace_share_rub' => 150.0,
                'teacher_share_rub' => 850.0,
            ])
            ->assertHeader('X-Correlation-ID', 'test-correlation-123');
    }

    public function test_enroll_b2b_success(): void
    {
        DB::table('courses')->insert([
            'id' => 1,
            'uuid' => 'course-uuid-1',
            'tenant_id' => tenant()->id,
            'teacher_id' => 1,
            'title' => 'Test Course',
            'price_kopecks' => 100000,
            'corporate_price_kopecks' => 80000,
            'status' => 'published',
        ]);

        DB::table('education_course_modules')->insert([
            'id' => 1,
            'course_id' => 1,
            'title' => 'Module 1',
            'order' => 1,
        ]);

        $response = $this->withToken($this->token)
            ->postJson('/api/v1/education/enrollments', [
                'user_id' => $this->user->id,
                'course_id' => 1,
                'corporate_contract_id' => 1,
            ], [
                'X-Correlation-ID' => 'test-correlation-456',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'mode' => 'b2b',
                'total_price_rub' => 800.0,
            ]);
    }

    public function test_update_progress_success(): void
    {
        DB::table('enrollments')->insert([
            'id' => 1,
            'uuid' => 'enroll-uuid-1',
            'tenant_id' => tenant()->id,
            'user_id' => $this->user->id,
            'course_id' => 1,
            'mode' => 'b2c',
            'status' => 'active',
            'progress_percent' => 0,
        ]);

        $response = $this->withToken($this->token)
            ->postJson('/api/v1/education/enrollments/1/progress', [
                'progress_percent' => 50,
            ], [
                'X-Correlation-ID' => 'test-correlation-789',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'enrollment_id' => 1,
                'progress_percent' => 50,
            ]);
    }

    public function test_cancel_enrollment_success(): void
    {
        DB::table('enrollments')->insert([
            'id' => 1,
            'uuid' => 'enroll-uuid-1',
            'tenant_id' => tenant()->id,
            'user_id' => $this->user->id,
            'course_id' => 1,
            'mode' => 'b2c',
            'status' => 'active',
            'progress_percent' => 0,
        ]);

        $response = $this->withToken($this->token)
            ->postJson('/api/v1/education/enrollments/1/cancel', [
                'reason' => 'User request',
            ], [
                'X-Correlation-ID' => 'test-correlation-cancel',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Enrollment cancelled',
            ]);
    }

    public function test_issue_certificate_success(): void
    {
        DB::table('enrollments')->insert([
            'id' => 1,
            'uuid' => 'enroll-uuid-1',
            'tenant_id' => tenant()->id,
            'user_id' => $this->user->id,
            'course_id' => 1,
            'mode' => 'b2c',
            'status' => 'completed',
            'progress_percent' => 100,
        ]);

        $response = $this->withToken($this->token)
            ->postJson('/api/v1/education/enrollments/1/certificate', [], [
                'X-Correlation-ID' => 'test-correlation-cert',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'certificate_id',
                'certificate_number',
                'issued_at',
                'valid_until',
            ]);
    }
}
