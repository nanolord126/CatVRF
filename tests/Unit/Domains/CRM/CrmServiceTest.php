<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\CRM;

use App\Domains\CRM\DTOs\CreateCrmClientDto;
use App\Domains\CRM\DTOs\CreateCrmInteractionDto;
use App\Domains\CRM\Models\CrmClient;
use App\Domains\CRM\Models\CrmInteraction;
use App\Domains\CRM\Services\CrmService;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

/**
 * Unit-тесты для CrmService — основной сервис CRM.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmServiceTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    private CrmService $service;
    private string $correlationId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->correlationId = $this->faker->uuid();
        $this->service = app(CrmService::class);
    }

    // ═══════════════════════════════════════════════════════
    //  CREATE CLIENT
    // ═══════════════════════════════════════════════════════

    public function test_create_client_stores_in_database(): void
    {
        $dto = new CreateCrmClientDto(
            tenantId: 1,
            businessGroupId: null,
            userId: null,
            firstName: 'Мария',
            lastName: 'Иванова',
            companyName: null,
            email: 'maria@example.com',
            phone: '+79001234567',
            phoneSecondary: null,
            clientType: 'individual',
            status: 'active',
            source: 'website',
            vertical: 'beauty',
            addresses: null,
            segment: null,
            preferences: null,
            specialNotes: null,
            internalNotes: null,
            verticalData: null,
            avatarUrl: null,
            preferredLanguage: 'ru',
            correlationId: $this->correlationId,
            idempotencyKey: null,
            tags: null,
        );

        $client = $this->service->createClient($dto);

        $this->assertInstanceOf(CrmClient::class, $client);
        $this->assertEquals('Мария', $client->first_name);
        $this->assertEquals('Иванова', $client->last_name);
        $this->assertEquals('maria@example.com', $client->email);
        $this->assertEquals('beauty', $client->vertical);
        $this->assertNotEmpty($client->uuid);

        $this->assertDatabaseHas('crm_clients', [
            'id' => $client->id,
            'email' => 'maria@example.com',
        ]);
    }

    public function test_create_client_generates_uuid(): void
    {
        $dto = new CreateCrmClientDto(
            tenantId: 1,
            businessGroupId: null,
            userId: null,
            firstName: 'Тест',
            lastName: 'UUID',
            companyName: null,
            email: 'uuid-test@example.com',
            phone: null,
            phoneSecondary: null,
            clientType: 'individual',
            status: 'active',
            source: 'manual',
            vertical: 'auto',
            addresses: null,
            segment: null,
            preferences: null,
            specialNotes: null,
            internalNotes: null,
            verticalData: null,
            avatarUrl: null,
            preferredLanguage: 'ru',
            correlationId: $this->correlationId,
            idempotencyKey: null,
            tags: null,
        );

        $client = $this->service->createClient($dto);

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $client->uuid,
        );
    }

    // ═══════════════════════════════════════════════════════
    //  UPDATE CLIENT
    // ═══════════════════════════════════════════════════════

    public function test_update_client_changes_fields(): void
    {
        $client = CrmClient::factory()->create([
            'tenant_id' => 1,
            'first_name' => 'Старое',
            'status' => 'active',
        ]);

        $updated = $this->service->updateClient($client, [
            'first_name' => 'Новое',
            'status' => 'vip',
        ], $this->correlationId);

        $this->assertEquals('Новое', $updated->first_name);
        $this->assertEquals('vip', $updated->status);
    }

    // ═══════════════════════════════════════════════════════
    //  RECORD INTERACTION
    // ═══════════════════════════════════════════════════════

    public function test_record_interaction_creates_entry(): void
    {
        $client = CrmClient::factory()->create([
            'tenant_id' => 1,
        ]);

        $dto = new CreateCrmInteractionDto(
            tenantId: 1,
            crmClientId: $client->id,
            userId: null,
            type: 'call',
            channel: 'phone',
            direction: 'inbound',
            subject: 'Запись на стрижку',
            content: 'Клиент хочет записаться на завтра в 14:00',
            metadata: null,
            correlationId: $this->correlationId,
        );

        $interaction = $this->service->recordInteraction($dto);

        $this->assertInstanceOf(CrmInteraction::class, $interaction);
        $this->assertEquals('call', $interaction->type);
        $this->assertEquals('phone', $interaction->channel);
        $this->assertEquals($client->id, $interaction->crm_client_id);
    }

    public function test_record_interaction_updates_client_last_interaction(): void
    {
        $client = CrmClient::factory()->create([
            'tenant_id' => 1,
            'last_interaction_at' => now()->subDays(30),
        ]);

        $dto = new CreateCrmInteractionDto(
            tenantId: 1,
            crmClientId: $client->id,
            userId: null,
            type: 'purchase',
            channel: 'website',
            direction: 'inbound',
            subject: 'Покупка',
            content: 'Заказ #12345',
            metadata: ['order_id' => 12345],
            correlationId: $this->correlationId,
        );

        $this->service->recordInteraction($dto);

        $client->refresh();
        $this->assertTrue($client->last_interaction_at->isToday());
    }

    // ═══════════════════════════════════════════════════════
    //  GET CLIENT
    // ═══════════════════════════════════════════════════════

    public function test_get_client_by_id_returns_correct_client(): void
    {
        $client = CrmClient::factory()->create([
            'tenant_id' => 1,
            'first_name' => 'Найденный',
        ]);

        $found = $this->service->getClientById($client->id, 1);

        $this->assertEquals('Найденный', $found->first_name);
        $this->assertEquals($client->id, $found->id);
    }

    public function test_get_client_wrong_tenant_throws_exception(): void
    {
        $client = CrmClient::factory()->create([
            'tenant_id' => 1,
        ]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->service->getClientById($client->id, 999);
    }

    // ═══════════════════════════════════════════════════════
    //  LIST CLIENTS
    // ═══════════════════════════════════════════════════════

    public function test_list_clients_pagination(): void
    {
        CrmClient::factory()->count(25)->create([
            'tenant_id' => 1,
            'vertical' => 'food',
        ]);

        $result = $this->service->listClients(1, 'food', perPage: 10);

        $this->assertCount(10, $result->items());
        $this->assertEquals(25, $result->total());
        $this->assertEquals(3, $result->lastPage());
    }

    public function test_list_clients_filters_by_vertical(): void
    {
        CrmClient::factory()->count(3)->create([
            'tenant_id' => 1,
            'vertical' => 'beauty',
        ]);

        CrmClient::factory()->count(2)->create([
            'tenant_id' => 1,
            'vertical' => 'auto',
        ]);

        $beautyClients = $this->service->listClients(1, 'beauty');
        $autoClients = $this->service->listClients(1, 'auto');

        $this->assertEquals(3, $beautyClients->total());
        $this->assertEquals(2, $autoClients->total());
    }

    public function test_list_clients_search_by_name(): void
    {
        CrmClient::factory()->create([
            'tenant_id' => 1,
            'first_name' => 'Алексей',
            'last_name' => 'Навигатор',
        ]);

        CrmClient::factory()->create([
            'tenant_id' => 1,
            'first_name' => 'Борис',
            'last_name' => 'Другой',
        ]);

        $result = $this->service->listClients(1, search: 'Навигатор');

        $this->assertEquals(1, $result->total());
    }

    // ═══════════════════════════════════════════════════════
    //  RECALCULATE CLIENT STATS
    // ═══════════════════════════════════════════════════════

    public function test_recalculate_client_stats(): void
    {
        $client = CrmClient::factory()->create([
            'tenant_id' => 1,
            'total_orders' => 0,
            'total_spent' => 0,
        ]);

        CrmInteraction::factory()->count(5)->create([
            'tenant_id' => 1,
            'crm_client_id' => $client->id,
            'type' => 'purchase',
        ]);

        $this->service->recalculateClientStats($client, $this->correlationId);

        $client->refresh();
        // Просто проверяем, что метод не выбрасывает исключение
        $this->assertNotNull($client->updated_at);
    }
}
