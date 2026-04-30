# Notifications Domain

## Overview
The Notifications domain handles all notification delivery across multiple channels including Email, Push notifications, SMS, Telegram, and in-app notifications.

## Architecture Layers

### Layer 1: Models
- **Notification** - Notification model with tenant scoping and delivery status
- **NotificationPreference** - User notification preferences per channel

### Layer 2: DTOs
- **SendNotificationDto** - Data transfer object for sending notifications
- **UpdatePreferenceDto** - Data transfer object for updating preferences

### Layer 3: Services
- **EmailService** - Email notification delivery
- **PushService** - Push notification delivery (FCM/APNs)
- **SmsService** - SMS notification delivery
- **TelegramService** - Telegram bot notifications
- **NotificationService** - Orchestration service for multi-channel delivery

### Layer 4: Requests
- **SendNotificationRequest** - Form request for sending notifications
- **UpdatePreferenceRequest** - Form request for updating preferences

### Layer 5: Resources
- **NotificationResource** - API resource for JSON serialization
- **NotificationPreferenceResource** - API resource for preferences

### Layer 6: Events
- **NotificationSentEvent** - Dispatched when notification is sent
- **NotificationFailedEvent** - Dispatched when notification fails

### Layer 7: Listeners
- **NotificationSentListener** - Handles successful notification delivery
- **NotificationFailedListener** - Handles failed notification retry logic

### Layer 8: Jobs
- **SendEmailJob** - Queued job for email delivery
- **SendPushJob** - Queued job for push notification
- **SendSmsJob** - Queued job for SMS delivery
- **SendTelegramJob** - Queued job for Telegram delivery
- **NotificationCleanupJob** - Scheduled job for cleaning old notifications

### Layer 9: Filament Resources
- **NotificationResource** - Admin UI for notification management
- **NotificationPreferenceResource** - Admin UI for user preferences

## Database Schema

### notifications Table
- `id` - Primary key
- `uuid` - Unique identifier
- `tenant_id` - Tenant (foreign key)
- `user_id` - User (foreign key)
- `type` - Notification type (email, push, sms, telegram, in_app)
- `channel` - Delivery channel
- `subject` - Notification subject
- `body` - Notification body
- `status` - Status (pending, sent, failed)
- `sent_at` - Sent timestamp
- `error_message` - Error message if failed
- `metadata` - JSON metadata
- `created_at`, `updated_at` - Timestamps

### notification_preferences Table
- `id` - Primary key
- `uuid` - Unique identifier
- `tenant_id` - Tenant (foreign key)
- `user_id` - User (foreign key)
- `channel` - Channel (email, push, sms, telegram, in_app)
- `is_enabled` - Enabled status
- `created_at`, `updated_at` - Timestamps

## Dependencies
- `App\Services\FraudControlService` - Fraud detection
- `App\Services\AuditService` - Audit logging
- `Illuminate\Database\DatabaseManager` - Database operations
- `Psr\Log\LoggerInterface` - Logging
- `Illuminate\Http\Client\Factory` - HTTP client for external APIs

## Usage Examples

### Send Email Notification
```php
$dto = new SendNotificationDto(
    tenantId: 1,
    userId: 5,
    type: 'email',
    subject: 'Order Confirmation',
    body: 'Your order has been confirmed',
    channel: 'email',
);
$notificationService->send($dto, $correlationId);
```

### Update User Preferences
```php
$dto = new UpdatePreferenceDto(
    userId: 5,
    channel: 'push',
    isEnabled: true,
);
$notificationService->updatePreference($dto, $correlationId);
```

### Send Multi-channel Notification
```php
$notificationService->sendMultiChannel(
    userId: 5,
    channels: ['email', 'push', 'telegram'],
    subject: 'Important Alert',
    body: 'Your attention is required',
    correlationId: $correlationId,
);
```

## Testing
Run domain tests with:
```bash
php artisan test --filter NotificationsDomain
```

## Queue Configuration
All jobs use the `notifications` queue as defined in `config/domain_queues.php`.
