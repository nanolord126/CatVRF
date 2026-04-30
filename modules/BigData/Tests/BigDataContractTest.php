<?php

declare(strict_types=1);

namespace Modules\BigData\Tests;

use Carbon\CarbonImmutable;
use Modules\BigData\Application\Services\BigDataService;
use Modules\BigData\Domain\DTOs\BaseEventDTO;
use Modules\BigData\Domain\DTOs\EventValidator;
use Modules\BigData\Domain\Enums\EventType;
use Modules\BigData\Infrastructure\ClickHouse\ClickHouseClient;
use Modules\BigData\Infrastructure\ClickHouse\ClickHouseService;
use Modules\BigData\Infrastructure\Kafka\KafkaProducerService;
use PHPUnit\Framework\TestCase;

/**
 * Big Data Contract Tests
 *
 * Tests the contract between components without external dependencies.
 * Uses mocks for ClickHouse and Kafka.
 */
final class BigDataContractTest extends TestCase
{
    private EventValidator $validator;
    private BaseEventDTO $sampleEvent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new EventValidator();
        $this->sampleEvent = BaseEventDTO::create(
            eventType: EventType::OrderPlaced,
            tenantId: 1,
            userId: 123,
            orderId: 456,
            monetaryValue: 99.99,
            properties: [
                'payment_method' => 'card',
                'items_count' => 3,
            ],
        );
    }

    public function test_event_validator_accepts_valid_event(): void
    {
        $this->expectNotToPerformAssertions();

        $this->validator->validate($this->sampleEvent);
    }

    public function test_event_validator_rejects_invalid_tenant_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tenant ID must be positive');

        $invalidEvent = BaseEventDTO::create(
            eventType: EventType::OrderPlaced,
            tenantId: -1,
            userId: 123,
            orderId: 456,
        );

        $this->validator->validate($invalidEvent);
    }

    public function test_event_validator_rejects_order_event_without_order_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Order ID is required for order events');

        $invalidEvent = BaseEventDTO::create(
            eventType: EventType::OrderPlaced,
            tenantId: 1,
            userId: 123,
            orderId: null,
        );

        $this->validator->validate($invalidEvent);
    }

    public function test_event_dto_serialization(): void
    {
        $array = $this->sampleEvent->toArray();
        $json = $this->sampleEvent->toJson();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('event_id', $array);
        $this->assertArrayHasKey('event_type', $array);
        $this->assertArrayHasKey('tenant_id', $array);

        $this->assertJson($json);

        $deserialized = BaseEventDTO::fromJson($json);
        $this->assertEquals($this->sampleEvent->eventId, $deserialized->eventId);
        $this->assertEquals($this->sampleEvent->eventType, $deserialized->eventType);
    }

    public function test_event_dto_anonymization(): void
    {
        $piiEvent = BaseEventDTO::create(
            eventType: EventType::UserRegistered,
            tenantId: 1,
            userId: 123,
            properties: [
                'email' => 'test@example.com',
                'phone' => '+1234567890',
            ],
            ipAddress: '192.168.1.1',
            userAgent: 'Mozilla/5.0',
        );

        $anonymized = $piiEvent->anonymize();

        $this->assertNull($anonymized->userId);
        $this->assertNull($anonymized->ipAddress);
        $this->assertNull($anonymized->userAgent);
        $this->assertNotEquals($piiEvent->properties['email'], $anonymized->properties['email']);
        $this->assertNotEquals($piiEvent->properties['phone'], $anonymized->properties['phone']);
    }

    public function test_event_type_category_mapping(): void
    {
        $this->assertEquals('order', EventType::OrderPlaced->getCategory());
        $this->assertEquals('product', EventType::ProductViewed->getCategory());
        $this->assertEquals('user', EventType::UserLoggedIn->getCategory());
        $this->assertEquals('seller', EventType::SellerRegistered->getCategory());
    }

    public function test_event_type_pii_detection(): void
    {
        $this->assertTrue(EventType::UserRegistered->isPIISensitive());
        $this->assertTrue(EventType::UserLoggedIn->isPIISensitive());
        $this->assertFalse(EventType::OrderPlaced->isPIISensitive());
        $this->assertFalse(EventType::ProductViewed->isPIISensitive());
    }

    public function test_event_type_clv_enrichment(): void
    {
        $this->assertTrue(EventType::OrderPlaced->shouldEnrichCLV());
        $this->assertTrue(EventType::OrderPaid->shouldEnrichCLV());
        $this->assertTrue(EventType::UserRegistered->shouldEnrichCLV());
        $this->assertFalse(EventType::ProductViewed->shouldEnrichCLV());
    }

    public function test_event_validator_json_schema(): void
    {
        $validJson = json_encode([
            'event_type' => 'order.placed',
            'tenant_id' => 1,
            'timestamp' => now()->toIso8601String(),
        ]);

        $this->assertTrue($this->validator->validateJsonSchema($validJson));

        $invalidJson = json_encode([
            'event_type' => 'invalid.event',
            'tenant_id' => 1,
        ]);

        $this->assertFalse($this->validator->validateJsonSchema($invalidJson));
    }
}
