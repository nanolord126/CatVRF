<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\CRM;

use App\Domains\CRM\DTOs\CreateCrmClientDto;
use App\Domains\CRM\DTOs\CreateCrmInteractionDto;
use App\Domains\CRM\DTOs\CreateCrmSegmentDto;
use App\Domains\CRM\DTOs\CreateCrmAutomationDto;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * Unit-тесты для CRM DTO — проверка конструкторов и toArray().
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmDtoTest extends TestCase
{
    use WithFaker;

    // ═══════════════════════════════════════════════════════
    //  CreateCrmClientDto
    // ═══════════════════════════════════════════════════════

    public function test_create_client_dto_initializes_correctly(): void
    {
        $dto = new CreateCrmClientDto(
            tenantId: 1,
            businessGroupId: 5,
            userId: 10,
            firstName: 'Иван',
            lastName: 'Петров',
            companyName: 'ООО Ромашка',
            email: 'ivan@example.com',
            phone: '+79001234567',
            phoneSecondary: '+79009876543',
            clientType: 'company',
            status: 'active',
            source: 'website',
            vertical: 'beauty',
            addresses: [['city' => 'Москва', 'street' => 'ул. Пушкина, 10']],
            segment: 'vip',
            preferences: ['notifications' => true],
            specialNotes: ['аллергия на латекс'],
            internalNotes: 'Важный клиент',
            verticalData: ['skin_type' => 'oily'],
            avatarUrl: 'https://example.com/avatar.jpg',
            preferredLanguage: 'ru',
            correlationId: 'test-corr-id',
            idempotencyKey: 'idemp-key-123',
            tags: ['vip', 'beauty'],
        );

        $this->assertEquals(1, $dto->tenantId);
        $this->assertEquals(5, $dto->businessGroupId);
        $this->assertEquals('Иван', $dto->firstName);
        $this->assertEquals('Петров', $dto->lastName);
        $this->assertEquals('ivan@example.com', $dto->email);
        $this->assertEquals('beauty', $dto->vertical);
        $this->assertEquals('test-corr-id', $dto->correlationId);
        $this->assertEquals('idemp-key-123', $dto->idempotencyKey);
        $this->assertCount(2, $dto->tags);
    }

    public function test_create_client_dto_with_minimal_params(): void
    {
        $dto = new CreateCrmClientDto(
            tenantId: 1,
            businessGroupId: null,
            userId: null,
            firstName: 'Минимальный',
            lastName: null,
            companyName: null,
            email: null,
            phone: null,
            phoneSecondary: null,
            clientType: 'individual',
            status: 'active',
            source: null,
            vertical: null,
            addresses: [],
            segment: null,
            preferences: [],
            specialNotes: [],
            internalNotes: null,
            verticalData: [],
            avatarUrl: null,
            preferredLanguage: 'ru',
            correlationId: $this->faker->uuid(),
        );

        $this->assertEquals('Минимальный', $dto->firstName);
        $this->assertNull($dto->lastName);
        $this->assertNull($dto->email);
        $this->assertEmpty($dto->addresses);
        $this->assertEmpty($dto->tags);
    }

    public function test_create_client_dto_to_array(): void
    {
        $dto = new CreateCrmClientDto(
            tenantId: 1,
            businessGroupId: null,
            userId: null,
            firstName: 'Тест',
            lastName: 'Массив',
            companyName: null,
            email: 'test@example.com',
            phone: null,
            phoneSecondary: null,
            clientType: 'individual',
            status: 'active',
            source: 'manual',
            vertical: 'auto',
            addresses: [],
            segment: null,
            preferences: [],
            specialNotes: [],
            internalNotes: null,
            verticalData: [],
            avatarUrl: null,
            preferredLanguage: 'ru',
            correlationId: 'corr-123',
        );

        $array = $dto->toArray();

        $this->assertIsArray($array);
        $this->assertEquals(1, $array['tenant_id']);
        $this->assertEquals('Тест', $array['first_name']);
        $this->assertEquals('auto', $array['vertical']);
        $this->assertEquals('corr-123', $array['correlation_id']);
    }

    // ═══════════════════════════════════════════════════════
    //  CreateCrmInteractionDto
    // ═══════════════════════════════════════════════════════

    public function test_create_interaction_dto_initializes_correctly(): void
    {
        $dto = new CreateCrmInteractionDto(
            tenantId: 1,
            crmClientId: 42,
            userId: 5,
            type: 'call',
            channel: 'phone',
            direction: 'inbound',
            subject: 'Запрос на запись',
            content: 'Клиент хочет записаться на стрижку',
            metadata: ['duration_minutes' => 5],
            correlationId: 'int-corr-123',
        );

        $this->assertEquals(1, $dto->tenantId);
        $this->assertEquals(42, $dto->crmClientId);
        $this->assertEquals('call', $dto->type);
        $this->assertEquals('phone', $dto->channel);
        $this->assertEquals('inbound', $dto->direction);
        $this->assertArrayHasKey('duration_minutes', $dto->metadata);
    }

    public function test_create_interaction_dto_with_minimal_params(): void
    {
        $dto = new CreateCrmInteractionDto(
            tenantId: 1,
            crmClientId: 1,
            userId: null,
            type: 'email',
            channel: null,
            direction: null,
            subject: null,
            content: 'Тестовое содержимое',
            metadata: [],
            correlationId: $this->faker->uuid(),
        );

        $this->assertEquals('email', $dto->type);
        $this->assertNull($dto->channel);
        $this->assertEmpty($dto->metadata);
    }

    // ═══════════════════════════════════════════════════════
    //  CreateCrmSegmentDto
    // ═══════════════════════════════════════════════════════

    public function test_create_segment_dto_initializes_correctly(): void
    {
        $rules = [
            ['field' => 'total_spent', 'operator' => '>=', 'value' => 50000],
            ['field' => 'vertical', 'operator' => '=', 'value' => 'beauty'],
        ];

        $dto = new CreateCrmSegmentDto(
            tenantId: 1,
            name: 'VIP Beauty',
            description: 'Клиенты beauty с оборотом > 50000',
            vertical: 'beauty',
            isDynamic: true,
            rules: $rules,
            correlationId: 'seg-corr-123',
            tags: ['vip', 'beauty'],
        );

        $this->assertEquals('VIP Beauty', $dto->name);
        $this->assertTrue($dto->isDynamic);
        $this->assertCount(2, $dto->rules);
        $this->assertEquals('total_spent', $dto->rules[0]['field']);
    }

    // ═══════════════════════════════════════════════════════
    //  CreateCrmAutomationDto
    // ═══════════════════════════════════════════════════════

    public function test_create_automation_dto_initializes_correctly(): void
    {
        $dto = new CreateCrmAutomationDto(
            tenantId: 1,
            name: 'Приветственное письмо',
            description: 'Отправка при первом визите',
            vertical: 'beauty',
            isActive: true,
            triggerType: 'new_client',
            triggerConfig: ['vertical' => 'beauty'],
            actionType: 'send_email',
            actionConfig: ['template' => 'welcome_beauty'],
            delayType: 'immediate',
            delayMinutes: 0,
            correlationId: 'auto-corr-123',
            tags: ['welcome'],
        );

        $this->assertEquals('Приветственное письмо', $dto->name);
        $this->assertTrue($dto->isActive);
        $this->assertEquals('new_client', $dto->triggerType);
        $this->assertEquals('send_email', $dto->actionType);
        $this->assertEquals(0, $dto->delayMinutes);
    }

    public function test_create_automation_dto_with_delay(): void
    {
        $dto = new CreateCrmAutomationDto(
            tenantId: 1,
            name: 'Напоминание через день',
            description: 'SMS через 1440 минут',
            vertical: 'auto',
            isActive: true,
            triggerType: 'sleeping_client',
            triggerConfig: ['days_inactive' => 30],
            actionType: 'send_sms',
            actionConfig: ['template' => 'comeback_auto'],
            delayType: 'minutes',
            delayMinutes: 1440,
            correlationId: $this->faker->uuid(),
            tags: null,
        );

        $this->assertEquals(1440, $dto->delayMinutes);
        $this->assertEquals('minutes', $dto->delayType);
    }
}
