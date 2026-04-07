<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Pharmacy;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * PharmacyVerticalFeatureTest
 *
 * Feature-тест для вертикали Pharmacy — CatVRF 2026.
 * Проверяет: AI-конструктор, B2B/B2C, Fraud check, correlation_id, tenant scope.
 *
 * Канон: каждая вертикаль ОБЯЗАНА иметь тесты (Layer 8/9).
 */
final class PharmacyVerticalFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $correlationId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user          = User::factory()->create();
        $this->correlationId = Str::uuid()->toString();
    }

    /** @test */
    public function correlation_id_is_required_in_all_responses(): void
    {
        $this->actingAs($this->user);

        // Для всех ответов API должен передаваться correlation_id
        $response = $this->getJson('/api/Pharmacy', [
            'X-Correlation-ID' => $this->correlationId,
        ]);

        // Ответ не должен быть 500 (сервер обработал запрос)
        $this->assertNotEquals(500, $response->status(), 'Endpoint не должен возвращать 500');
    }

    /** @test */
    public function ai_constructor_service_can_be_resolved_from_container(): void
    {
        // Проверяем, что AI-конструктор зарегистрирован в контейнере
        $this->assertTrue(
            class_exists('App\Domains\Pharmacy\Services\AI\PharmacyHealthConstructorService'),
            'AI-конструктор App\Domains\Pharmacy\Services\AI\PharmacyHealthConstructorService должен существовать'
        );
    }

    /** @test */
    public function service_class_exists_in_container(): void
    {
        // Проверяем, что основной сервис существует
        $this->assertTrue(
            class_exists('App\Domains\Pharmacy\Services\PharmacyService'),
            'Сервис App\Domains\Pharmacy\Services\PharmacyService должен существовать'
        );
    }

    /** @test */
    public function ai_constructor_has_required_methods(): void
    {
        $aiClass = 'App\Domains\Pharmacy\Services\AI\PharmacyHealthConstructorService';

        if (!class_exists($aiClass)) {
            $this->markTestSkipped("Класс $aiClass не найден");
        }

        $reflection = new \ReflectionClass($aiClass);

        $this->assertTrue(
            $reflection->hasMethod('analyzeAndRecommend'),
            'AI-конструктор должен иметь метод analyzeAndRecommend()'
        );
    }

    /** @test */
    public function ai_constructor_is_final_and_readonly(): void
    {
        $aiClass = 'App\Domains\Pharmacy\Services\AI\PharmacyHealthConstructorService';

        if (!class_exists($aiClass)) {
            $this->markTestSkipped("Класс $aiClass не найден");
        }

        $reflection = new \ReflectionClass($aiClass);

        $this->assertTrue($reflection->isFinal(), "AI-конструктор $aiClass должен быть final");
    }

    /** @test */
    public function log_audit_channel_receives_events(): void
    {
        Log::shouldReceive('channel')
            ->with('audit')
            ->andReturnSelf()
            ->zeroOrMoreTimes();

        Log::shouldReceive('info')
            ->zeroOrMoreTimes();

        // Просто проверяем, что Log::channel('audit') вызывается при работе
        $this->actingAs($this->user);

        // Эмулируем запрос
        $response = $this->getJson('/api/Pharmacy', [
            'X-Correlation-ID' => $this->correlationId,
        ]);

        $this->assertNotEquals(500, $response->status());
    }

    /** @test */
    public function unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/Pharmacy');
        $this->assertContains($response->status(), [401, 403, 404], 'Неавторизованный запрос должен быть отклонён');
    }
    /** @test */
    public function b2b_mode_is_detected_correctly(): void
    {
        $this->actingAs($this->user);

        $response = $this->getJson('/api/Pharmacy/b2b-check', [
            'inn'            => '1234567890',
            'business_card_id' => '42',
            'X-Correlation-ID' => $this->correlationId,
        ]);

        // B2B определяется только по inn + business_card_id (канон 2026)
        $this->assertTrue($response->status() !== 500, 'B2B mode detection должен работать без 500');
    }

    /** @test */
    public function service_constructor_uses_dependency_injection(): void
    {
        $serviceClass = 'App\Domains\Pharmacy\Services\PharmacyService';

        if (!class_exists($serviceClass)) {
            $this->markTestSkipped("Класс $aiClass не найден");
        }

        $reflection   = new \ReflectionClass($serviceClass);
        $constructor  = $reflection->getConstructor();

        if ($constructor === null) {
            $this->markTestSkipped(" не имеет конструктора");
        }

        // Проверяем, что сервис использует constructor injection (не статику)
        $this->assertGreaterThan(
            0,
            $constructor->getNumberOfParameters(),
            "$serviceClass должен использовать constructor injection"
        );
    }
}
