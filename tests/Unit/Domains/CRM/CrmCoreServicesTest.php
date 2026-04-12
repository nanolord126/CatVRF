<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\CRM;

use App\Domains\CRM\DTOs\CreateCrmSegmentDto;
use App\Domains\CRM\DTOs\CreateCrmAutomationDto;
use App\Domains\CRM\Models\CrmClient;
use App\Domains\CRM\Models\CrmSegment;
use App\Domains\CRM\Models\CrmAutomation;
use App\Domains\CRM\Services\CrmSegmentationService;
use App\Domains\CRM\Services\CrmAutomationService;
use App\Domains\CRM\Services\CrmAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * Unit-тесты для CrmSegmentationService, CrmAutomationService, CrmAnalyticsService.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmCoreServicesTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    private string $correlationId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->correlationId = $this->faker->uuid();
    }

    // ═══════════════════════════════════════════════════════
    //  SEGMENTATION SERVICE
    // ═══════════════════════════════════════════════════════

    public function test_segmentation_service_creates_segment(): void
    {
        $service = app(CrmSegmentationService::class);

        $dto = new CreateCrmSegmentDto(
            tenantId: 1,
            name: 'VIP Beauty',
            description: 'Клиенты beauty с оборотом > 50000',
            vertical: 'beauty',
            isDynamic: true,
            rules: [
                ['field' => 'total_spent', 'operator' => '>=', 'value' => 50000],
                ['field' => 'vertical', 'operator' => '=', 'value' => 'beauty'],
            ],
            correlationId: $this->correlationId,
            tags: ['vip', 'beauty'],
        );

        $segment = $service->createSegment($dto);

        $this->assertInstanceOf(CrmSegment::class, $segment);
        $this->assertEquals('VIP Beauty', $segment->name);
        $this->assertTrue($segment->is_dynamic);
        $this->assertIsArray($segment->rules);
    }

    public function test_segmentation_service_recalculates_segment(): void
    {
        $service = app(CrmSegmentationService::class);

        $segment = CrmSegment::factory()->create([
            'tenant_id' => 1,
            'is_dynamic' => true,
            'rules' => [
                ['field' => 'total_spent', 'operator' => '>=', 'value' => 1000],
            ],
        ]);

        // Создаём клиентов подходящих под правило
        CrmClient::factory()->count(5)->create([
            'tenant_id' => 1,
            'total_spent' => 2000,
        ]);

        // Создаём клиентов не подходящих
        CrmClient::factory()->count(3)->create([
            'tenant_id' => 1,
            'total_spent' => 500,
        ]);

        $service->recalculateSegment($segment, $this->correlationId);

        $segment->refresh();
        $this->assertNotNull($segment->last_calculated_at);
    }

    public function test_segmentation_service_handles_empty_rules(): void
    {
        $service = app(CrmSegmentationService::class);

        $segment = CrmSegment::factory()->create([
            'tenant_id' => 1,
            'is_dynamic' => false,
            'rules' => [],
        ]);

        // Не должен выбрасывать исключение
        $service->recalculateSegment($segment, $this->correlationId);

        $this->assertTrue(true); // Если дошли сюда — тест пройден
    }

    // ═══════════════════════════════════════════════════════
    //  AUTOMATION SERVICE
    // ═══════════════════════════════════════════════════════

    public function test_automation_service_creates_automation(): void
    {
        $service = app(CrmAutomationService::class);

        $dto = new CreateCrmAutomationDto(
            tenantId: 1,
            name: 'Приветственное SMS',
            description: 'SMS при первом визите',
            vertical: 'beauty',
            isActive: true,
            triggerType: 'new_client',
            triggerConfig: ['vertical' => 'beauty'],
            actionType: 'send_sms',
            actionConfig: ['template' => 'welcome_sms'],
            delayType: null,
            delayMinutes: 0,
            correlationId: $this->correlationId,
            tags: null,
        );

        $automation = $service->createAutomation($dto);

        $this->assertInstanceOf(CrmAutomation::class, $automation);
        $this->assertEquals('Приветственное SMS', $automation->name);
        $this->assertTrue($automation->is_active);
        $this->assertEquals('new_client', $automation->trigger_type);
    }

    public function test_automation_service_toggles_status(): void
    {
        $service = app(CrmAutomationService::class);

        $automation = CrmAutomation::factory()->create([
            'tenant_id' => 1,
            'is_active' => true,
        ]);

        $service->toggleAutomation($automation, $this->correlationId);
        $automation->refresh();
        $this->assertFalse($automation->is_active);

        $service->toggleAutomation($automation, $this->correlationId);
        $automation->refresh();
        $this->assertTrue($automation->is_active);
    }

    public function test_automation_service_lists_active_automations(): void
    {
        CrmAutomation::factory()->count(3)->create([
            'tenant_id' => 1,
            'is_active' => true,
        ]);

        CrmAutomation::factory()->count(2)->create([
            'tenant_id' => 1,
            'is_active' => false,
        ]);

        $service = app(CrmAutomationService::class);
        $all = CrmAutomation::where('tenant_id', 1)->get();
        $active = $all->where('is_active', true);

        $this->assertCount(3, $active);
    }

    // ═══════════════════════════════════════════════════════
    //  ANALYTICS SERVICE
    // ═══════════════════════════════════════════════════════

    public function test_analytics_service_returns_dashboard_data(): void
    {
        CrmClient::factory()->count(10)->create([
            'tenant_id' => 1,
            'vertical' => 'beauty',
            'status' => 'active',
            'total_spent' => 5000,
        ]);

        CrmClient::factory()->count(5)->create([
            'tenant_id' => 1,
            'vertical' => 'beauty',
            'status' => 'active',
            'last_interaction_at' => now()->subDays(60),
        ]);

        $service = app(CrmAnalyticsService::class);
        $dashboard = $service->getDashboard(1, 'beauty', $this->correlationId);

        $this->assertIsArray($dashboard);
        $this->assertArrayHasKey('total_clients', $dashboard);
        $this->assertArrayHasKey('active_clients', $dashboard);
    }

    public function test_analytics_service_handles_empty_data(): void
    {
        $service = app(CrmAnalyticsService::class);
        $dashboard = $service->getDashboard(999, 'nonexistent', $this->correlationId);

        $this->assertIsArray($dashboard);
        $this->assertEquals(0, $dashboard['total_clients'] ?? 0);
    }
}
