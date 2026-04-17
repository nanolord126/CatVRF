<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\RealEstate;

use Tests\TestCase;
use App\Domains\RealEstate\DTOs\BookViewingDto;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class BookViewingDtoTest extends TestCase
{
    use RefreshDatabase;

    public function test_from_request_creates_dto_with_b2c(): void
    {
        $request = Request::create('/api/viewings/book', 'POST', [
            'property_id' => 1,
            'scheduled_at' => Carbon::now()->addDays(2)->toIso8601String(),
        ]);

        $request->headers->set('X-Correlation-ID', 'test-correlation-id');
        $request->setUserResolver(fn () => (object) ['id' => 123]);

        $dto = BookViewingDto::from($request);

        $this->assertInstanceOf(BookViewingDto::class, $dto);
        $this->assertEquals(123, $dto->userId);
        $this->assertEquals(1, $dto->propertyId);
        $this->assertEquals('test-correlation-id', $dto->correlationId);
        $this->assertFalse($dto->isB2B);
    }

    public function test_from_request_creates_dto_with_b2b(): void
    {
        $request = Request::create('/api/viewings/book', 'POST', [
            'property_id' => 1,
            'scheduled_at' => Carbon::now()->addDays(2)->toIso8601String(),
            'inn' => '123456789012',
            'business_card_id' => 456,
        ]);

        $request->headers->set('X-Correlation-ID', 'test-correlation-id');
        $request->setUserResolver(fn () => (object) ['id' => 123]);

        $dto = BookViewingDto::from($request);

        $this->assertTrue($dto->isB2B);
    }

    public function test_from_request_generates_correlation_id_if_missing(): void
    {
        $request = Request::create('/api/viewings/book', 'POST', [
            'property_id' => 1,
            'scheduled_at' => Carbon::now()->addDays(2)->toIso8601String(),
        ]);

        $request->setUserResolver(fn () => (object) ['id' => 123]);

        $dto = BookViewingDto::from($request);

        $this->assertNotNull($dto->correlationId);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $dto->correlationId);
    }

    public function test_to_array_returns_correct_structure(): void
    {
        $dto = new BookViewingDto(
            tenantId: 1,
            businessGroupId: 2,
            userId: 123,
            correlationId: 'test-correlation-id',
            propertyId: 456,
            scheduledAt: Carbon::parse('2026-01-15 14:00:00'),
            isB2B: false,
            idempotencyKey: 'test-key',
            metadata: ['test' => 'data']
        );

        $array = $dto->toArray();

        $this->assertIsArray($array);
        $this->assertEquals(1, $array['tenant_id']);
        $this->assertEquals(2, $array['business_group_id']);
        $this->assertEquals(123, $array['user_id']);
        $this->assertEquals('test-correlation-id', $array['correlation_id']);
        $this->assertEquals(456, $array['property_id']);
        $this->assertEquals('2026-01-15T14:00:00+00:00', $array['scheduled_at']);
        $this->assertFalse($array['is_b2b']);
        $this->assertEquals(['test' => 'data'], $array['metadata']);
    }

    public function test_scheduled_at_is_carbon_instance(): void
    {
        $dto = new BookViewingDto(
            tenantId: 1,
            businessGroupId: null,
            userId: 123,
            correlationId: 'test-correlation-id',
            propertyId: 456,
            scheduledAt: Carbon::parse('2026-01-15 14:00:00'),
            isB2B: false
        );

        $this->assertInstanceOf(Carbon::class, $dto->scheduledAt);
        $this->assertEquals('2026-01-15 14:00:00', $dto->scheduledAt->toDateTimeString());
    }

    public function test_properties_are_readonly(): void
    {
        $dto = new BookViewingDto(
            tenantId: 1,
            businessGroupId: null,
            userId: 123,
            correlationId: 'test-correlation-id',
            propertyId: 456,
            scheduledAt: Carbon::now(),
            isB2B: false
        );

        $this->expectException(\Error::class);
        $dto->propertyId = 999;
    }

    public function test_optional_parameters_default_to_null(): void
    {
        $dto = new BookViewingDto(
            tenantId: 1,
            businessGroupId: null,
            userId: 123,
            correlationId: 'test-correlation-id',
            propertyId: 456,
            scheduledAt: Carbon::now(),
            isB2B: false
        );

        $this->assertNull($dto->idempotencyKey);
        $this->assertNull($dto->metadata);
    }
}
