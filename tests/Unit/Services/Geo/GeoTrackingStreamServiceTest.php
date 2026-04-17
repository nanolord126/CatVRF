<?php declare(strict_types=1);

namespace Tests\Unit\Services\Geo;

use App\Services\Geo\GeoTrackingStreamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

final class GeoTrackingStreamServiceTest extends TestCase
{
    use RefreshDatabase;

    private GeoTrackingStreamService $streamService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->streamService = app(GeoTrackingStreamService::class);
        Redis::flushdb();
    }

    public function test_add_location_update_returns_message_id(): void
    {
        $messageId = $this->streamService->addLocationUpdate(
            entityId: 1,
            entityType: 'courier',
            lat: 55.75,
            lon: 37.62,
            speed: 30.5,
            bearing: 45.0,
        );
        
        $this->assertIsString($messageId);
        $this->assertNotEmpty($messageId);
    }

    public function test_read_entity_updates_returns_array(): void
    {
        // Add a location update first
        $this->streamService->addLocationUpdate(
            entityId: 1,
            entityType: 'courier',
            lat: 55.75,
            lon: 37.62,
        );
        
        $updates = $this->streamService->readEntityUpdates(1, 'courier', 10);
        
        $this->assertIsArray($updates);
        $this->assertNotEmpty($updates);
    }

    public function test_create_consumer_group(): void
    {
        $this->expectNotToPerformAssertions();
        
        $this->streamService->createConsumerGroup('test_consumer');
    }

    public function test_read_from_consumer_group_returns_array(): void
    {
        $this->streamService->createConsumerGroup('test_consumer');
        
        // Add a message to stream
        $this->streamService->addLocationUpdate(
            entityId: 1,
            entityType: 'courier',
            lat: 55.75,
            lon: 37.62,
        );
        
        $messages = $this->streamService->readFromConsumerGroup('test_consumer', 1, 100);
        
        $this->assertIsArray($messages);
    }

    public function test_acknowledge_message_returns_bool(): void
    {
        // Add a message
        $messageId = $this->streamService->addLocationUpdate(
            entityId: 1,
            entityType: 'courier',
            lat: 55.75,
            lon: 37.62,
        );
        
        $acknowledged = $this->streamService->acknowledgeMessage($messageId);
        
        $this->assertIsBool($acknowledged);
    }

    public function test_get_stream_info_returns_array(): void
    {
        $info = $this->streamService->getStreamInfo();
        
        $this->assertIsArray($info);
        $this->assertArrayHasKey('length', $info);
        $this->assertArrayHasKey('groups', $info);
    }

    public function test_trim_stream(): void
    {
        $this->expectNotToPerformAssertions();
        
        $this->streamService->trimStream(100);
    }

    public function test_delete_old_messages_returns_int(): void
    {
        // Add some messages
        for ($i = 0; $i < 5; $i++) {
            $this->streamService->addLocationUpdate(
                entityId: $i,
                entityType: 'courier',
                lat: 55.75,
                lon: 37.62,
            );
        }
        
        $deleted = $this->streamService->deleteOldMessages(24);
        
        $this->assertIsInt($deleted);
    }

    public function test_add_location_update_with_different_entity_types(): void
    {
        $courierId = $this->streamService->addLocationUpdate(
            entityId: 1,
            entityType: 'courier',
            lat: 55.75,
            lon: 37.62,
        );
        
        $doctorId = $this->streamService->addLocationUpdate(
            entityId: 2,
            entityType: 'doctor',
            lat: 55.76,
            lon: 37.63,
        );
        
        $this->assertIsString($courierId);
        $this->assertIsString($doctorId);
        $this->assertNotEquals($courierId, $doctorId);
    }
}
