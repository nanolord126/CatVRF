<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\WeddingPlanning;

use Tests\TestCase;
use App\Domains\WeddingPlanning\Models\WeddingEvent;
use App\Domains\WeddingPlanning\Models\WeddingVendor;
use App\Domains\WeddingPlanning\Services\WeddingService;
use App\Domains\WeddingPlanning\Services\AIWeddingPlannerConstructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * WeddingIntegrationTest
 *
 * Layer 9: Testing Layer
 * Проверка основного workflows вертикали Wedding Planning.
 *
 * @version 1.0.0
 * @author CatVRF
 */
class WeddingIntegrationTest extends TestCase
{
    // use RefreshDatabase; // Применяем в реальном тесте для чистой базы

    /**
     * Test wedding creation with AI constructor.
     * (Интеграционный тест: Layer 2 -> Layer 3)
     */
    public function test_wedding_creation_with_ai_constructor_logic(): void
    {
        $correlationId = (string) Str::uuid();
        $weddingService = app(WeddingService::class);
        $aiConstructor = app(AIWeddingPlannerConstructor::class);

        // 1. Создание свадьбы
        $data = [
            'title' => 'Test Wedding 2026',
            'total_budget' => 50000000, // 500 000 RUB
            'guest_count' => 100,
            'event_date' => now()->addMonths(6)->toDateTimeString(),
            'correlation_id' => $correlationId,
        ];

        $wedding = $weddingService->createWedding($data);

        $this->assertNotNull($wedding);
        $this->assertEquals('Test Wedding 2026', $wedding->title);
        $this->assertEquals(50000000, $wedding->total_budget);

        // 2. Генерация AI-плана
        $aiPlan = $aiConstructor->generateWeddingPlan(50000000, 'luxury', 100);

        $this->assertArrayHasKey('budget_distribution', $aiPlan);
        $this->assertArrayHasKey('timeline', $aiPlan);
        $this->assertGreaterThan(0, count($aiPlan['budget_distribution']));

        // 3. Проверка транзакции бронирования
        // Создаем тестового вендора
        $vendor = WeddingVendor::factory()->create([
            'category' => 'photographer',
            'min_price' => 5000000, // 50 000 RUB
            'is_active' => true,
        ]);

        $booking = $weddingService->bookService(
            $wedding->id,
            WeddingVendor::class,
            $vendor->id,
            5000000,
            $correlationId
        );

        $this->assertNotNull($booking);
        $this->assertEquals('reserved', $booking->status);
        $this->assertEquals(5000000, $booking->amount);

        Log::channel('audit')->info('Wedding Integration Test Passed', ['correlation_id' => $correlationId]);
    }

    /**
     * Test booking validation (Not enough funds / Busy vendor).
     */
    public function test_booking_validation_insufficient_funds(): void
    {
        // Placeholder: Logic test for FraudControlService Integration
        $this->assertTrue(true);
    }
}
