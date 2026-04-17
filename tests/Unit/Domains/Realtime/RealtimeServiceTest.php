<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Realtime;

use App\Services\Realtime\RealtimeService;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\LogManager;
use Tests\TestCase;

final class RealtimeServiceTest extends TestCase
{
    private RealtimeService $service;
    private DatabaseManager $db;
    private AuditService $audit;
    private FraudControlService $fraud;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = app(DatabaseManager::class);
        $this->audit = app(AuditService::class);
        $this->fraud = app(FraudControlService::class);
        
        $this->service = new RealtimeService(
            $this->db,
            app(LogManager::class),
            $this->audit,
            $this->fraud,
        );
    }

    public function test_broadcast_to_user(): void
    {
        $result = $this->service->broadcastToUser(
            userId: 1,
            channel: 'notifications',
            event: 'new-notification',
            data: ['message' => 'Test notification'],
            correlationId: 'test-123',
        );

        $this->assertTrue($result);
    }

    public function test_broadcast_to_tenant(): void
    {
        $result = $this->service->broadcastToTenant(
            tenantId: 1,
            channel: 'updates',
            event: 'system-update',
            data: ['update' => 'System maintenance scheduled'],
            correlationId: 'test-123',
        );

        $this->assertTrue($result);
    }

    public function test_broadcast_to_business_group(): void
    {
        $result = $this->service->broadcastToBusinessGroup(
            tenantId: 1,
            businessGroupId: 1,
            channel: 'orders',
            event: 'new-order',
            data: ['order_id' => 1],
            correlationId: 'test-123',
        );

        $this->assertTrue($result);
    }

    public function test_register_websocket_connection(): void
    {
        $connectionId = $this->service->registerConnection(
            userId: 1,
            tenantId: 1,
            businessGroupId: null,
            deviceType: 'web',
            ipAddress: '127.0.0.1',
            correlationId: 'test-123',
        );

        $this->assertIsInt($connectionId);
        $this->assertDatabaseHas('realtime_connections', [
            'id' => $connectionId,
            'user_id' => 1,
            'tenant_id' => 1,
            'device_type' => 'web',
            'is_active' => true,
        ]);
    }

    public function test_unregister_websocket_connection(): void
    {
        $connectionId = $this->service->registerConnection(
            userId: 1,
            tenantId: 1,
            businessGroupId: null,
            deviceType: 'web',
            ipAddress: '127.0.0.1',
            correlationId: 'test-123',
        );

        $result = $this->service->unregisterConnection($connectionId, 'test-123');

        $this->assertTrue($result);
        $this->assertDatabaseHas('realtime_connections', [
            'id' => $connectionId,
            'is_active' => false,
        ]);
    }

    public function test_get_active_connections_for_user(): void
    {
        $this->service->registerConnection(1, 1, null, 'web', '127.0.0.1', 'test-123');
        $this->service->registerConnection(1, 1, null, 'mobile', '127.0.0.1', 'test-123');

        $connections = $this->service->getActiveConnectionsForUser(1);

        $this->assertCount(2, $connections);
    }

    public function test_get_active_connections_for_tenant(): void
    {
        $this->service->registerConnection(1, 1, null, 'web', '127.0.0.1', 'test-123');
        $this->service->registerConnection(2, 1, null, 'mobile', '127.0.0.1', 'test-123');

        $connections = $this->service->getActiveConnectionsForTenant(1);

        $this->assertCount(2, $connections);
    }

    public function test_cleanup_inactive_connections(): void
    {
        $connectionId = $this->service->registerConnection(1, 1, null, 'web', '127.0.0.1', 'test-123');

        // Mark as inactive by setting last_heartbeat to old time
        $this->db->table('realtime_connections')
            ->where('id', $connectionId)
            ->update(['last_heartbeat' => now()->subMinutes(15)]);

        $cleaned = $this->service->cleanupInactiveConnections(10, 'test-123');

        $this->assertGreaterThan(0, $cleaned);
    }

    public function test_send_presence_update(): void
    {
        $connectionId = $this->service->registerConnection(1, 1, null, 'web', '127.0.0.1', 'test-123');

        $result = $this->service->sendPresenceUpdate(
            connectionId: $connectionId,
            presence: 'online',
            metadata: ['page' => '/dashboard'],
            correlationId: 'test-123',
        );

        $this->assertTrue($result);
    }

    public function test_get_presence_for_user(): void
    {
        $connectionId = $this->service->registerConnection(1, 1, null, 'web', '127.0.0.1', 'test-123');

        $this->service->sendPresenceUpdate($connectionId, 'online', ['page' => '/dashboard'], 'test-123');

        $presence = $this->service->getPresenceForUser(1);

        $this->assertIsArray($presence);
        $this->assertEquals('online', $presence['presence']);
    }

    protected function tearDown(): void
    {
        $this->db->table('realtime_connections')->truncate();
        parent::tearDown();
    }
}
