<?php declare(strict_types=1);

namespace Tests\Feature\Domains\Education;

use Tests\TestCase;
use App\Domains\Education\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use App\Domains\Education\Events\PriceUpdatedEvent;

final class DynamicPricingApiTest extends TestCase
{
    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    public function test_calculate_price_unauthorized(): void
    {
        $response = $this->postJson('/api/v1/education/pricing/calculate', [
            'course_id' => 1,
        ]);

        $response->assertStatus(401);
    }

    public function test_calculate_price_success(): void
    {
        $course = Course::factory()->create([
            'tenant_id' => tenant()->id,
            'price_kopecks' => 100000,
        ]);

        Event::fake([PriceUpdatedEvent::class]);

        $response = $this->withToken($this->token)
            ->postJson('/api/v1/education/pricing/calculate', [
                'course_id' => $course->id,
                'user_segment' => 'standard',
            ], [
                'X-Correlation-ID' => 'test-correlation-123',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'price_id',
                'original_price_rub',
                'adjusted_price_rub',
                'discount_percent',
                'adjustment_reason',
                'factors',
                'valid_until',
                'is_flash_sale',
                'generated_at',
            ])
            ->assertHeader('X-Correlation-ID', 'test-correlation-123');

        Event::assertDispatched(PriceUpdatedEvent::class);
    }

    public function test_calculate_price_validation_error(): void
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/v1/education/pricing/calculate', [
                'course_id' => 999999,
                'user_segment' => 'invalid_segment',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['course_id', 'user_segment']);
    }

    public function test_trigger_flash_sale_success(): void
    {
        $course = Course::factory()->create([
            'tenant_id' => tenant()->id,
            'price_kopecks' => 100000,
        ]);

        $response = $this->withToken($this->token)
            ->postJson("/api/v1/education/pricing/flash-sale/{$course->id}", [
                'discount_percent' => 25,
            ], [
                'X-Correlation-ID' => 'flash-sale-correlation',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'original_price_rub' => 1000.0,
                'adjusted_price_rub' => 750.0,
                'discount_percent' => 25,
                'is_flash_sale' => true,
            ])
            ->assertHeader('X-Correlation-ID', 'flash-sale-correlation');
    }

    public function test_trigger_flash_sale_validation_error(): void
    {
        $course = Course::factory()->create([
            'tenant_id' => tenant()->id,
        ]);

        $response = $this->withToken($this->token)
            ->postJson("/api/v1/education/pricing/flash-sale/{$course->id}", [
                'discount_percent' => 50,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['discount_percent']);
    }

    public function test_calculate_price_b2b_mode(): void
    {
        $course = Course::factory()->create([
            'tenant_id' => tenant()->id,
            'price_kopecks' => 100000,
            'corporate_price_kopecks' => 80000,
        ]);

        $response = $this->withToken($this->token)
            ->postJson('/api/v1/education/pricing/calculate', [
                'course_id' => $course->id,
                'inn' => '1234567890',
                'business_card_id' => 1,
            ], [
                'X-Correlation-ID' => 'b2b-correlation',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'original_price_rub' => 800.0,
            ]);
    }
}
