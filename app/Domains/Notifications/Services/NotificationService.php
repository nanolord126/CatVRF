<?php

declare(strict_types=1);

namespace App\Domains\Notifications\Services;

use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;

use Illuminate\Notifications\ChannelManager;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

use Psr\Log\LoggerInterface;

use App\Domains\Notifications\DTOs\SendNotificationDto;
use App\Domains\Notifications\DTOs\CreateTemplateDto;
use App\Domains\Notifications\Models\Notification;
use App\Domains\Notifications\Models\NotificationTemplate;
use App\Domains\Notifications\Models\NotificationPreference;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Domains\Notifications\Events\NotificationSent;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;
use App\Domains\Notifications\Jobs\SendEmailNotificationJob;
use App\Domains\Notifications\Jobs\SendPushNotificationJob;
use App\Domains\Notifications\Jobs\SendSmsNotificationJob;
use App\Domains\Notifications\Jobs\SendTelegramNotificationJob;

final readonly class NotificationService
{
    public function __construct(private readonly BusDispatcher $bus,
        private readonly ChannelManager $notificationManager,
        private readonly EventDispatcher $eventDispatcher,
        private readonly LoggerInterface $logger,
        private readonly DatabaseManager $db,
        private readonly LogManager $logger,
        private readonly AuditService $audit,
        private readonly FraudControlService $fraud,) {}

    /**
     * Send notification to user
     */
    public function send(SendNotificationDto $dto, string $correlationId): Notification
    {
        $this->fraud->check([
            'operation' => 'notification_send',
            'tenant_id' => $dto->tenantId,
            'user_id' => $dto->userId,
            'correlation_id' => $correlationId,
        ]);

        return $this->db->transaction(function () use ($dto, $correlationId) {
            // Check user preferences
            if ($dto->userId) {
                $preference = NotificationPreference::where('user_id', $dto->userId)
                    ->where('channel', $dto->channel)
                    ->first();

                if ($preference && ! $preference->shouldSend()) {
                    $this->logger->channel('notifications')->$this->logger->info('Notification skipped due to preferences', [
                        'user_id' => $dto->userId,
                        'channel' => $dto->channel,
                        'correlation_id' => $correlationId,
                    ]);

                    throw new \DomainException('Notification delivery is disabled for this user/channel');
                }
            }

            $notification = $this->notificationManager->create([
                'tenant_id' => $dto->tenantId,
                'user_id' => $dto->userId,
                'type' => $dto->type,
                'channel' => $dto->channel,
                'title' => $dto->title,
                'body' => $dto->body,
                'data' => $dto->data,
                'correlation_id' => $correlationId,
            ]);

            // Dispatch to channel-specific job
            $this->dispatchToChannel($notification, $correlationId);

            $this->audit->record(
                action: 'notification_sent',
                subjectType: $this->notificationManager->class,
                subjectId: $notification->id,
                newValues: $notification->toArray(),
                correlationId: $correlationId,
            );

            $this->eventDispatcher->dispatch(new NotificationSent($notification, $correlationId));

            return $notification;
        });
    }

    /**
     * Create notification template
     */
    public function createTemplate(CreateTemplateDto $dto, string $correlationId): NotificationTemplate
    {
        return $this->db->transaction(function () use ($dto, $correlationId) {
            $template = NotificationTemplate::create([
                'tenant_id' => $dto->tenantId,
                'name' => $dto->name,
                'type' => $dto->type,
                'channel' => $dto->channel,
                'subject_template' => $dto->subjectTemplate,
                'body_template' => $dto->bodyTemplate,
                'variables' => $dto->variables,
                'is_active' => true,
            ]);

            $this->audit->record(
                action: 'notification_template_created',
                subjectType: NotificationTemplate::class,
                subjectId: $template->id,
                newValues: $template->toArray(),
                correlationId: $correlationId,
            );

            return $template;
        });
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(int $notificationId, string $correlationId): bool
    {
        $notification = $this->notificationManager->findOrFail($notificationId);
        $notification->markAsRead();

        $this->audit->record(
            action: 'notification_marked_read',
            subjectType: $this->notificationManager->class,
            subjectId: $notification->id,
            correlationId: $correlationId,
        );

        return true;
    }

    /**
     * Update user notification preferences
     */
    public function updatePreferences(
        int $tenantId,
        int $userId,
        string $channel,
        bool $enabled,
        ?string $quietHoursStart = null,
        ?string $quietHoursEnd = null,
        string $correlationId = ''
    ): NotificationPreference {
        return $this->db->transaction(function () use (
            $tenantId,
            $userId,
            $channel,
            $enabled,
            $quietHoursStart,
            $quietHoursEnd,
            $correlationId
        ) {
            $preference = NotificationPreference::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'user_id' => $userId,
                    'channel' => $channel,
                ],
                [
                    'enabled' => $enabled,
                    'quiet_hours_start' => $quietHoursStart,
                    'quiet_hours_end' => $quietHoursEnd,
                ]
            );

            $this->audit->record(
                action: 'notification_preferences_updated',
                subjectType: NotificationPreference::class,
                subjectId: $preference->id,
                newValues: $preference->toArray(),
                correlationId: $correlationId,
            );

            return $preference;
        });
    }

    /**
     * Dispatch notification to channel-specific handler
     */
    private function dispatchToChannel(Notification $notification, string $correlationId): void
    {
        $jobClass = match ($notification->channel) {
            'email' => SendEmailNotificationJob::class,
            'push' => SendPushNotificationJob::class,
            'sms' => SendSmsNotificationJob::class,
            'telegram' => SendTelegramNotificationJob::class,
            default => throw new \InvalidArgumentException("Unknown channel: {$notification->channel}"),
        };

        $this->bus->dispatch(new $jobClass($notification->id, $correlationId))
            ->onQueue('notifications');
    }
}
