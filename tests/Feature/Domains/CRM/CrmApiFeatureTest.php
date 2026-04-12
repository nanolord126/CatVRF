<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\CRM;

use App\Domains\CRM\DTOs\CreateCrmClientDto;
use App\Domains\CRM\Models\CrmClient;
use App\Domains\CRM\Models\CrmInteraction;
use App\Domains\CRM\Models\CrmSegment;
use App\Domains\CRM\Models\CrmAutomation;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

/**
 * CRM API Feature Tests — полный цикл CRUD + сегменты + автоматизации.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmApiFeatureTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    private int $tenantId = 1;
    private string $correlationId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->correlationId = $this->faker->uuid();
    }

    // ═══════════════════════════════════════════════════════
    //  CLIENTS — CRUD
    // ═══════════════════════════════════════════════════════

    public function test_can_create_crm_client(): void
    {
        $payload = [
            'first_name' => 'Иван',
            'last_name' => 'Петров',
            'email' => 'ivan.petrov@example.com',
            'phone' => '+79001234567',
            'client_type' => 'individual',
            'status' => 'active',
            'source' => 'website',
            'vertical' => 'beauty',
        ];

        $response = $this->postJson('/api/v1/crm/clients', $payload, [
            'X-Correlation-ID' => $this->correlationId,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.first_name', 'Иван')
            ->assertJsonPath('data.last_name', 'Петров')
            ->assertJsonPath('data.email', 'ivan.petrov@example.com')
            ->assertJsonPath('data.vertical', 'beauty')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonStructure([
                'data' => ['id', 'uuid', 'first_name', 'last_name', 'email', 'phone', 'vertical'],
                'correlation_id',
            ]);

        $this->assertDatabaseHas('crm_clients', [
            'email' => 'ivan.petrov@example.com',
            'vertical' => 'beauty',
        ]);
    }

    public function test_create_client_validates_required_fields(): void
    {
        $response = $this->postJson('/api/v1/crm/clients', [], [
            'X-Correlation-ID' => $this->correlationId,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['first_name']);
    }

    public function test_can_list_crm_clients(): void
    {
        CrmClient::factory()->count(5)->create([
            'tenant_id' => $this->tenantId,
            'vertical' => 'beauty',
        ]);

        $response = $this->getJson('/api/v1/crm/clients?vertical=beauty', [
            'X-Correlation-ID' => $this->correlationId,
        ]);

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [['id', 'first_name', 'last_name', 'vertical']],
                'meta',
                'correlation_id',
            ]);
    }

    public function test_can_show_single_client(): void
    {
        $client = CrmClient::factory()->create([
            'tenant_id' => $this->tenantId,
            'vertical' => 'auto',
        ]);

        $response = $this->getJson("/api/v1/crm/clients/{$client->id}", [
            'X-Correlation-ID' => $this->correlationId,
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('data.id', $client->id)
            ->assertJsonPath('data.vertical', 'auto');
    }

    public function test_show_nonexistent_client_returns_404(): void
    {
        $response = $this->getJson('/api/v1/crm/clients/999999', [
            'X-Correlation-ID' => $this->correlationId,
        ]);

        $response->assertStatus(404);
    }

    public function test_can_update_client(): void
    {
        $client = CrmClient::factory()->create([
            'tenant_id' => $this->tenantId,
            'first_name' => 'Старое Имя',
        ]);

        $response = $this->putJson("/api/v1/crm/clients/{$client->id}", [
            'first_name' => 'Новое Имя',
            'status' => 'vip',
        ], [
            'X-Correlation-ID' => $this->correlationId,
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('data.first_name', 'Новое Имя')
            ->assertJsonPath('data.status', 'vip');

        $this->assertDatabaseHas('crm_clients', [
            'id' => $client->id,
            'first_name' => 'Новое Имя',
            'status' => 'vip',
        ]);
    }

    public function test_can_search_clients_by_name(): void
    {
        CrmClient::factory()->create([
            'tenant_id' => $this->tenantId,
            'first_name' => 'Александр',
            'last_name' => 'Пушкин',
        ]);

        CrmClient::factory()->create([
            'tenant_id' => $this->tenantId,
            'first_name' => 'Борис',
            'last_name' => 'Годунов',
        ]);

        $response = $this->getJson('/api/v1/crm/clients?search=Пушкин', [
            'X-Correlation-ID' => $this->correlationId,
        ]);

        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Пушкин', $data[0]['last_name']);
    }

    public function test_can_filter_clients_by_status(): void
    {
        CrmClient::factory()->count(3)->create([
            'tenant_id' => $this->tenantId,
            'status' => 'active',
        ]);

        CrmClient::factory()->count(2)->create([
            'tenant_id' => $this->tenantId,
            'status' => 'vip',
        ]);

        $response = $this->getJson('/api/v1/crm/clients?status=vip', [
            'X-Correlation-ID' => $this->correlationId,
        ]);

        $response->assertSuccessful();
        $this->assertCount(2, $response->json('data'));
    }

    // ═══════════════════════════════════════════════════════
    //  INTERACTIONS
    // ═══════════════════════════════════════════════════════

    public function test_can_create_interaction(): void
    {
        $client = CrmClient::factory()->create([
            'tenant_id' => $this->tenantId,
        ]);

        $payload = [
            'type' => 'call',
            'channel' => 'phone',
            'direction' => 'inbound',
            'content' => 'Клиент звонил по поводу записи на стрижку',
        ];

        $response = $this->postJson(
            "/api/v1/crm/clients/{$client->id}/interactions",
            $payload,
            ['X-Correlation-ID' => $this->correlationId],
        );

        $response->assertStatus(201)
            ->assertJsonPath('data.type', 'call')
            ->assertJsonPath('data.channel', 'phone')
            ->assertJsonPath('data.direction', 'inbound');

        $this->assertDatabaseHas('crm_interactions', [
            'crm_client_id' => $client->id,
            'type' => 'call',
            'channel' => 'phone',
        ]);
    }

    public function test_can_list_client_interactions(): void
    {
        $client = CrmClient::factory()->create([
            'tenant_id' => $this->tenantId,
        ]);

        CrmInteraction::factory()->count(5)->create([
            'tenant_id' => $this->tenantId,
            'crm_client_id' => $client->id,
        ]);

        $response = $this->getJson(
            "/api/v1/crm/clients/{$client->id}/interactions",
            ['X-Correlation-ID' => $this->correlationId],
        );

        $response->assertSuccessful()
            ->assertJsonCount(5, 'data');
    }

    public function test_interaction_updates_last_interaction_at(): void
    {
        $client = CrmClient::factory()->create([
            'tenant_id' => $this->tenantId,
            'last_interaction_at' => null,
        ]);

        $this->postJson(
            "/api/v1/crm/clients/{$client->id}/interactions",
            [
                'type' => 'email',
                'channel' => 'email',
                'direction' => 'outbound',
                'content' => 'Отправлено приветственное письмо',
            ],
            ['X-Correlation-ID' => $this->correlationId],
        );

        $client->refresh();
        $this->assertNotNull($client->last_interaction_at);
    }

    // ═══════════════════════════════════════════════════════
    //  SLEEPING CLIENTS
    // ═══════════════════════════════════════════════════════

    public function test_sleeping_clients_endpoint(): void
    {
        // Спящий клиент — последнее взаимодействие >30 дней назад
        CrmClient::factory()->create([
            'tenant_id' => $this->tenantId,
            'vertical' => 'beauty',
            'last_interaction_at' => now()->subDays(60),
        ]);

        // Активный клиент
        CrmClient::factory()->create([
            'tenant_id' => $this->tenantId,
            'vertical' => 'beauty',
            'last_interaction_at' => now()->subDays(5),
        ]);

        $response = $this->getJson('/api/v1/crm/clients/sleeping?vertical=beauty', [
            'X-Correlation-ID' => $this->correlationId,
        ]);

        $response->assertSuccessful();
    }

    // ═══════════════════════════════════════════════════════
    //  SEGMENTS
    // ═══════════════════════════════════════════════════════

    public function test_can_create_segment(): void
    {
        $payload = [
            'name' => 'VIP клиенты Beauty',
            'description' => 'Клиенты с LTV > 50000',
            'vertical' => 'beauty',
            'is_dynamic' => true,
            'rules' => [
                ['field' => 'total_spent', 'operator' => '>=', 'value' => 50000],
            ],
        ];

        $response = $this->postJson('/api/v1/crm/segments', $payload, [
            'X-Correlation-ID' => $this->correlationId,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'VIP клиенты Beauty')
            ->assertJsonPath('data.is_dynamic', true);

        $this->assertDatabaseHas('crm_segments', [
            'name' => 'VIP клиенты Beauty',
            'vertical' => 'beauty',
        ]);
    }

    public function test_can_list_segments(): void
    {
        CrmSegment::factory()->count(3)->create([
            'tenant_id' => $this->tenantId,
        ]);

        $response = $this->getJson('/api/v1/crm/segments', [
            'X-Correlation-ID' => $this->correlationId,
        ]);

        $response->assertSuccessful()
            ->assertJsonCount(3, 'data');
    }

    public function test_can_recalculate_segment(): void
    {
        $segment = CrmSegment::factory()->create([
            'tenant_id' => $this->tenantId,
            'is_dynamic' => true,
            'rules' => [
                ['field' => 'total_spent', 'operator' => '>=', 'value' => 1000],
            ],
        ]);

        $response = $this->postJson(
            "/api/v1/crm/segments/{$segment->id}/recalculate",
            [],
            ['X-Correlation-ID' => $this->correlationId],
        );

        $response->assertSuccessful();
    }

    // ═══════════════════════════════════════════════════════
    //  AUTOMATIONS
    // ═══════════════════════════════════════════════════════

    public function test_can_create_automation(): void
    {
        $payload = [
            'name' => 'Приветственное письмо',
            'description' => 'Отправка при первом визите',
            'vertical' => 'beauty',
            'trigger_type' => 'new_client',
            'trigger_config' => ['vertical' => 'beauty'],
            'action_type' => 'send_email',
            'action_config' => ['template' => 'welcome_beauty'],
        ];

        $response = $this->postJson('/api/v1/crm/automations', $payload, [
            'X-Correlation-ID' => $this->correlationId,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Приветственное письмо')
            ->assertJsonPath('data.trigger_type', 'new_client');

        $this->assertDatabaseHas('crm_automations', [
            'name' => 'Приветственное письмо',
        ]);
    }

    public function test_can_toggle_automation(): void
    {
        $automation = CrmAutomation::factory()->create([
            'tenant_id' => $this->tenantId,
            'is_active' => true,
        ]);

        $response = $this->postJson(
            "/api/v1/crm/automations/{$automation->id}/toggle",
            [],
            ['X-Correlation-ID' => $this->correlationId],
        );

        $response->assertSuccessful()
            ->assertJsonPath('data.is_active', false);

        // Повторный toggle — включаем обратно
        $response2 = $this->postJson(
            "/api/v1/crm/automations/{$automation->id}/toggle",
            [],
            ['X-Correlation-ID' => $this->correlationId],
        );

        $response2->assertSuccessful()
            ->assertJsonPath('data.is_active', true);
    }

    public function test_can_list_automations(): void
    {
        CrmAutomation::factory()->count(3)->create([
            'tenant_id' => $this->tenantId,
        ]);

        $response = $this->getJson('/api/v1/crm/automations', [
            'X-Correlation-ID' => $this->correlationId,
        ]);

        $response->assertSuccessful()
            ->assertJsonCount(3, 'data');
    }

    // ═══════════════════════════════════════════════════════
    //  ANALYTICS DASHBOARD
    // ═══════════════════════════════════════════════════════

    public function test_dashboard_returns_metrics(): void
    {
        CrmClient::factory()->count(10)->create([
            'tenant_id' => $this->tenantId,
            'vertical' => 'beauty',
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v1/crm/analytics/dashboard?vertical=beauty', [
            'X-Correlation-ID' => $this->correlationId,
        ]);

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    'total_clients',
                    'active_clients',
                    'new_clients_week',
                    'sleeping_clients',
                ],
                'correlation_id',
            ]);
    }

    // ═══════════════════════════════════════════════════════
    //  CORRELATION ID
    // ═══════════════════════════════════════════════════════

    public function test_response_contains_correlation_id(): void
    {
        $response = $this->getJson('/api/v1/crm/clients', [
            'X-Correlation-ID' => $this->correlationId,
        ]);

        $response->assertJsonStructure(['correlation_id']);
        $this->assertEquals($this->correlationId, $response->json('correlation_id'));
    }
}
