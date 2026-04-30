# Realtime Domain

## Overview
The Realtime domain handles WebSocket connections, real-time message broadcasting, and chat functionality with connection management and fraud protection.

## Architecture Layers

### Layer 1: Models
- **WebSocketConnection** - WebSocket connection model with tenant scoping

### Layer 2: DTOs
- **RegisterConnectionDto** - Data transfer object for connection registration

### Layer 3: Services
- **RealtimeService** - WebSocket registration, broadcasting, and disconnection

### Layer 4: Requests
- **RegisterConnectionRequest** - Form request for connection registration

### Layer 5: Resources
- **WebSocketConnectionResource** - API resource for connections

### Layer 6: Events
- **RealtimeMessage** - Broadcastable event for WebSocket messages

### Layer 7: Listeners
- (None - events are handled by broadcasting system)

### Layer 8: Jobs
- **ConnectionCleanupJob** - Scheduled job for cleaning stale connections
- **BroadcastJob** - Queued job for message broadcasting

### Layer 9: Filament Resources
- **WebSocketConnectionResource** - Admin UI for connection monitoring

## Database Schema

### websocket_connections Table
- `id` - Primary key
- `uuid` - Unique identifier
- `tenant_id` - Tenant (foreign key)
- `user_id` - User (foreign key)
- `channel` - Channel name
- `connection_id` - WebSocket connection ID
- `is_active` - Active status
- `metadata` - JSON metadata
- `created_at`, `updated_at` - Timestamps

## Dependencies
- `Illuminate\Broadcasting\BroadcastManager` - Broadcasting system
- `App\Services\FraudControlService` - Fraud detection
- `App\Services\AuditService` - Audit logging
- `Illuminate\Database\DatabaseManager` - Database operations
- `Psr\Log\LoggerInterface` - Logging
- `Illuminate\Support\Str` - UUID generation

## Usage Examples

### Register WebSocket Connection
```php
$connection = $realtimeService->registerConnection(
    userId: 5,
    channel: 'orders.123',
    connectionId: 'abc123',
    correlationId: $correlationId,
);
```

### Broadcast Message
```php
$realtimeService->broadcast(
    channel: 'orders.123',
    event: 'order.updated',
    data: ['order_id' => 123, 'status' => 'processing'],
    correlationId: $correlationId,
);
```

### Disconnect WebSocket
```php
$realtimeService->disconnect($connectionId, $correlationId);
```

### Get Active Connections for Channel
```php
$connections = $realtimeService->getActiveConnections('orders.123');
```

### Get User Connections
```php
$connections = $realtimeService->getUserConnections($userId);
```

### Disconnect All User Connections
```php
$realtimeService->disconnectUser($userId, $correlationId);
```

## Testing
Run domain tests with:
```bash
php artisan test --filter RealtimeDomain
```

## Queue Configuration
All jobs use the `realtime` queue as defined in `config/domain_queues.php`.
