# Webhooks Domain

## Overview
The Webhooks domain handles webhook management including registration, signature validation, delivery, and retry logic.

## Architecture Layers

### Layer 1: Models
- **Webhook** - Webhook configuration model with tenant scoping
- **WebhookLog** - Webhook delivery log model

### Layer 2: DTOs
- **RegisterWebhookDto** - Data transfer object for webhook registration
- **WebhookPayloadDto** - Data transfer object for webhook payload

### Layer 3: Services
- **WebhookManagementService** - Webhook CRUD operations
- **SignatureValidator** - HMAC signature validation
- **WebhookDeliveryService** - Webhook delivery and retry logic

### Layer 4: Requests
- **RegisterWebhookRequest** - Form request for webhook registration
- **WebhookPayloadRequest** - Form request for webhook payload

### Layer 5: Resources
- **WebhookResource** - API resource for webhooks
- **WebhookLogResource** - API resource for delivery logs

### Layer 6: Events
- **WebhookDeliveredEvent** - Dispatched when webhook is delivered
- **WebhookFailedEvent** - Dispatched when webhook delivery fails

### Layer 7: Listeners
- **WebhookDeliveredListener** - Handles successful webhook delivery
- **WebhookFailedListener** - Handles failed webhook retry logic

### Layer 8: Jobs
- **DeliverWebhookJob** - Queued job for webhook delivery
- **WebhookRetryJob** - Queued job for retrying failed webhooks
- **WebhookCleanupJob** - Scheduled job for cleaning old logs

### Layer 9: Filament Resources
- **WebhookResource** - Admin UI for webhook management
- **WebhookLogResource** - Admin UI for delivery logs

## Database Schema

### webhooks Table
- `id` - Primary key
- `uuid` - Unique identifier
- `tenant_id` - Tenant (foreign key)
- `url` - Webhook URL
- `events` - JSON array of events to subscribe to
- `secret` - HMAC secret for signature validation
- `is_active` - Active status
- `timeout_seconds` - Request timeout
- `retry_policy` - Retry policy configuration
- `created_at`, `updated_at` - Timestamps

### webhook_logs Table
- `id` - Primary key
- `uuid` - Unique identifier
- `tenant_id` - Tenant (foreign key)
- `webhook_id` - Webhook (foreign key)
- `event_type` - Event type
- `payload` - JSON payload
- `response_code` - HTTP response code
- `response_body` - Response body
- `attempt_count` - Number of delivery attempts
- `status` - Status (pending, delivered, failed)
- `delivered_at` - Delivered timestamp
- `created_at`, `updated_at` - Timestamps

## Dependencies
- `App\Services\FraudControlService` - Fraud detection
- `App\Services\AuditService` - Audit logging
- `Illuminate\Database\DatabaseManager` - Database operations
- `Psr\Log\LoggerInterface` - Logging
- `Illuminate\Http\Client\Factory` - HTTP client for webhook delivery

## Usage Examples

### Register Webhook
```php
$dto = new RegisterWebhookDto(
    tenantId: 1,
    url: 'https://example.com/webhook',
    events: ['order.created', 'order.paid'],
    secret: 'webhook_secret_key',
    timeoutSeconds: 30,
);
$webhook = $webhookManagementService->register($dto, $correlationId);
```

### Deliver Webhook
```php
$payload = new WebhookPayloadDto(
    eventType: 'order.created',
    data: ['order_id' => 123, 'amount' => 99900],
);
$webhookDeliveryService->deliver($webhook, $payload, $correlationId);
```

### Validate Signature
```php
$isValid = $signatureValidator->validate(
    payload: $request->getContent(),
    signature: $request->header('X-Webhook-Signature'),
    secret: $webhook->secret,
);
```

## Testing
Run domain tests with:
```bash
php artisan test --filter WebhooksDomain
```

## Queue Configuration
All jobs use the `webhooks` queue as defined in `config/domain_queues.php`.
