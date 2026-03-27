<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\EventPlanning;

use App\Domains\EventPlanning\Models\Event;
use App\Domains\EventPlanning\Services\EventPlanningService;
use App\Domains\EventPlanning\Services\EventAIService;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Illuminate\Support\Str;
use App\Domains\Payments\Services\WalletService;
use App\Services\FraudControlService;
use Mockery\MockInterface;

/**
 * EventPlanningIntegrationTest.
 * Канон 2026: Multi-tenant, Service Injection, UUID, Financial Integrity.
 * Интеграционный тест всего флоу планировщика событий.
 */
final class EventPlanningIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $plannerUser;
    private Tenant $activeTenant;

    /**
     * Пре-конфигурация окружения (Prepare)
     */
    protected function setUp(): void
    {
        parent::setUp();

        // 1. Мокаем внешние сервисы
        $this->mock(WalletService::class, function (MockInterface $mock) {
            $mock->shouldReceive('hold')->andReturn(true);
            $mock->shouldReceive('release')->andReturn(true);
            $mock->shouldReceive('getOrCreateWallet')->andReturn(new \stdClass());
        });

        $this->mock(FraudControlService::class, function (MockInterface $mock) {
            $mock->shouldReceive('check')->andReturn(true);
            $mock->shouldReceive('scoreOperation')->andReturn(0.1);
        });

        // 2. Создаем тенента
        $this->activeTenant = Tenant::factory()->create();

        // 3. Создаем пользователя-планировщика в этом тененте
        $this->plannerUser = User::factory()->create([
            'tenant_id' => $this->activeTenant->id,
            'role' => 'event_planner'
        ]);

        $this->actingAs($this->plannerUser);
    }

    /**
     * Сценарий 1: Успешная генерация плана через AI (Happy Path)
     */
    public function test_can_generate_and_store_ai_event_plan(): void
    {
        /** @var EventPlanningService $service */
        $service = app(EventPlanningService::class);

        $eventData = [
            'tenant_id' => $this->activeTenant->id,
            'uuid' => (string) Str::uuid(),
            'correlation_id' => (string) Str::uuid(),
            'title' => 'Корпоратив 2026',
            'type' => 'corporate',
            'event_date' => now()->addDays(30),
            'location' => 'Moscow City Loft',
            'guest_count' => 50,
            'total_budget_kopecks' => 50000000, // 500 000 руб
            'status' => 'draft',
        ];

        // 1. Создаем черновик
        $event = $service->initializePlanning($eventData);

        // Assert: Проверка в БД (Layer 0 & 1)
        $this->assertDatabaseHas('events', [
            'uuid' => $event->uuid,
            'title' => 'Корпоратив 2026',
            'status' => 'draft',
        ]);

        // 2. Генерируем AI сценарий
        $plan = $service->generateScenario($event);

        // Assert: Проверка Layer 3
        $this->assertNotNull($plan['timeline']);
        $this->assertNotNull($plan['budget_breakdown']);
        
        // Assert: Проверка логирования
        $this->assertDatabaseHas('events', [
            'uuid' => $event->uuid,
            'status' => 'planning', // Статус должен смениться
        ]);
    }

    /**
     * Сценарий 2: Отмена события со штрафом (Financial Accuracy)
     */
    public function test_event_cancellation_calculates_correct_fee(): void
    {
        $service = app(EventPlanningService::class);

        // Событие завтра (штраф 100%)
        $urgentEvent = Event::factory()->create([
            'tenant_id' => $this->activeTenant->id,
            'event_date' => now()->addDay(),
            'total_budget_kopecks' => 10000000, // 100 000 руб
            'status' => 'confirmed'
        ]);

        $service->cancelEvent($urgentEvent, 'Client changed mind last minute');

        $this->assertDatabaseHas('events', [
            'uuid' => $urgentEvent->uuid,
            'status' => 'cancelled',
            'cancellation_fee_kopecks' => 10000000,
        ]);

        // Событие через 40 дней (штраф 10%)
        $longTermEvent = Event::factory()->create([
            'tenant_id' => $this->activeTenant->id,
            'event_date' => now()->addDays(40),
            'total_budget_kopecks' => 10000000,
            'status' => 'confirmed'
        ]);

        $service->cancelEvent($longTermEvent, 'Early cancellation');

        $this->assertDatabaseHas('events', [
            'uuid' => $longTermEvent->uuid,
            'status' => 'cancelled',
            'cancellation_fee_kopecks' => 1000000, // 10%
        ]);
    }

    /**
     * Сценарий 3: Изоляция тенентов (Security Isolation)
     */
    public function test_event_tenant_isolation_prevents_unauthorized_access(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherEvent = Event::factory()->create([
            'tenant_id' => $otherTenant->id,
            'title' => 'Конкурентный праздник',
        ]);

        // Пытаемся получить через наш скоп (plannerUser)
        $events = Event::all();

        $this->assertCount(0, $events);
        $this->assertFalse($events->contains('uuid', $otherEvent->uuid));
    }
}
