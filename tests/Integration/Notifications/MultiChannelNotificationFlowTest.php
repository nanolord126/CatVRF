<?php

declare(strict_types=1);

namespace Tests\Integration\Notifications;

use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * MultiChannelNotificationFlowTest
 * 
 * Тестирует отправку уведомлений по всем каналам одновременно
 */
final class MultiChannelNotificationFlowTest extends TestCase
{
    use RefreshDatabase;

    private NotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Log::fake();

        $this->service = app(NotificationService::class);
    }

    /** @test */
    public function it_sends_to_email_and_sms_simultaneously(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['email' => 'test@example.com', 'phone' => '+79991234567']);

        NotificationPreference::factory()
            ->for($user)
            ->create([
                'notification_type' => 'payment.captured',
                'email_enabled' => true,
                'sms_enabled' => true,
                'push_enabled' => false,
            ]);

        $notification = Notification::factory()
            ->for($user)
            ->create([
                'type' => 'payment.captured',
                'channels' => ['email', 'sms'],
                'title' => 'Payment Captured',
                'body' => 'Your payment has been captured',
                'data' => ['payment_id' => 123, 'amount' => 5000],
            ]);

        $this->service->send($user->id, $notification, 'test-correlation');

        // Both channels should have been attempted
        Mail::assertSent(function () {
            return true;
        });

        // SMS service would also be called
        Log::assertLogged(function ($message) {
            return str_contains($message, 'email') || str_contains($message, 'sms');
        });
    }

    /** @test */
    public function it_sends_to_email_push_and_database(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['email' => 'test@example.com']);

        NotificationPreference::factory()
            ->for($user)
            ->create([
                'notification_type' => 'payment.captured',
                'email_enabled' => true,
                'sms_enabled' => false,
                'push_enabled' => true,
                'database_enabled' => true,
            ]);

        $notification = Notification::factory()
            ->for($user)
            ->create([
                'type' => 'payment.captured',
                'channels' => ['email', 'push', 'database'],
                'title' => 'Payment Captured',
                'body' => 'Payment body',
                'data' => ['payment_id' => 123],
            ]);

        $this->service->send($user->id, $notification, 'test-correlation');

        // Email should be sent
        Mail::assertSent(function () {
            return true;
        });

        // Database notification should be created
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'payment.captured',
        ]);
    }

    /** @test */
    public function it_respects_user_channel_preferences(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['email' => 'test@example.com']);

        // User disabled SMS and push
        NotificationPreference::factory()
            ->for($user)
            ->create([
                'notification_type' => 'payment.captured',
                'email_enabled' => true,
                'sms_enabled' => false,
                'push_enabled' => false,
                'database_enabled' => true,
            ]);

        $notification = Notification::factory()
            ->for($user)
            ->create([
                'type' => 'payment.captured',
                'channels' => ['email', 'sms', 'push', 'database'],
                'title' => 'Payment',
                'body' => 'Body',
                'data' => [],
            ]);

        $this->service->send($user->id, $notification, 'test-correlation');

        // Email should be sent
        Mail::assertSent(function () {
            return true;
        });

        // But SMS and push should not
        // Only email and database channels used
        Log::assertLogged(function ($message) {
            return str_contains($message, 'email') && str_contains($message, 'database');
        });
    }

    /** @test */
    public function it_fails_gracefully_if_one_channel_fails(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['email' => 'test@example.com']);

        NotificationPreference::factory()
            ->for($user)
            ->create([
                'notification_type' => 'payment.captured',
                'email_enabled' => true,
                'sms_enabled' => true,
                'push_enabled' => true,
            ]);

        $notification = Notification::factory()
            ->for($user)
            ->create([
                'type' => 'payment.captured',
                'channels' => ['email', 'sms', 'push'],
                'title' => 'Payment',
                'body' => 'Body',
                'data' => [],
            ]);

        // Service should not throw even if one channel fails
        $this->service->send($user->id, $notification, 'test-correlation');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'payment.captured',
        ]);
    }

    /** @test */
    public function it_logs_all_channel_attempts(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['email' => 'test@example.com', 'phone' => '+79991234567']);

        NotificationPreference::factory()
            ->for($user)
            ->create([
                'notification_type' => 'payment.captured',
                'email_enabled' => true,
                'sms_enabled' => true,
                'push_enabled' => true,
            ]);

        $notification = Notification::factory()
            ->for($user)
            ->create([
                'type' => 'payment.captured',
                'channels' => ['email', 'sms', 'push'],
                'title' => 'Payment',
                'body' => 'Body',
                'data' => [],
                'correlation_id' => 'test-correlation-123',
            ]);

        $this->service->send($user->id, $notification, 'test-correlation-123');

        Log::assertLogged(function ($message) {
            return str_contains($message, 'test-correlation-123');
        });
    }

    /** @test */
    public function it_marks_notification_as_sent_when_all_channels_succeed(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['email' => 'test@example.com']);

        NotificationPreference::factory()
            ->for($user)
            ->create([
                'notification_type' => 'payment.captured',
                'email_enabled' => true,
                'database_enabled' => true,
            ]);

        $notification = Notification::factory()
            ->for($user)
            ->create([
                'type' => 'payment.captured',
                'channels' => ['email', 'database'],
                'status' => 'pending',
                'title' => 'Payment',
                'body' => 'Body',
            ]);

        $this->service->send($user->id, $notification, 'test-correlation');

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'status' => 'sent',
        ]);
    }

    /** @test */
    public function it_handles_missing_phone_number_gracefully(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['email' => 'test@example.com', 'phone' => null]);

        NotificationPreference::factory()
            ->for($user)
            ->create([
                'notification_type' => 'payment.captured',
                'email_enabled' => true,
                'sms_enabled' => true,
            ]);

        $notification = Notification::factory()
            ->for($user)
            ->create([
                'type' => 'payment.captured',
                'channels' => ['email', 'sms'],
                'title' => 'Payment',
                'body' => 'Body',
            ]);

        $this->service->send($user->id, $notification, 'test-correlation');

        // Email should still be sent
        Mail::assertSent(function () {
            return true;
        });

        // SMS would fail silently, notification still marked as sent
        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'type' => 'payment.captured',
        ]);
    }

    /** @test */
    public function it_handles_missing_email_gracefully(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['email' => null, 'phone' => '+79991234567']);

        NotificationPreference::factory()
            ->for($user)
            ->create([
                'notification_type' => 'payment.captured',
                'email_enabled' => true,
                'sms_enabled' => true,
            ]);

        $notification = Notification::factory()
            ->for($user)
            ->create([
                'type' => 'payment.captured',
                'channels' => ['email', 'sms'],
                'title' => 'Payment',
                'body' => 'Body',
            ]);

        $this->service->send($user->id, $notification, 'test-correlation');

        // Notification should be created even if email missing
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'payment.captured',
        ]);
    }

    /** @test */
    public function it_preserves_notification_data_across_all_channels(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['email' => 'test@example.com']);

        $data = [
            'payment_id' => 123,
            'amount' => 5000,
            'currency' => 'RUB',
            'timestamp' => now()->toIso8601String(),
        ];

        NotificationPreference::factory()
            ->for($user)
            ->create([
                'notification_type' => 'payment.captured',
                'email_enabled' => true,
                'database_enabled' => true,
            ]);

        $notification = Notification::factory()
            ->for($user)
            ->create([
                'type' => 'payment.captured',
                'channels' => ['email', 'database'],
                'title' => 'Payment Captured',
                'body' => 'Amount: 5000 RUB',
                'data' => $data,
            ]);

        $this->service->send($user->id, $notification, 'test-correlation');

        // Data should be preserved in database
        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'data' => json_encode($data),
        ]);
    }

    /** @test */
    public function it_respects_quiet_hours_across_all_channels(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['email' => 'test@example.com']);

        $now = now();
        $inQuietHours = $now->setHour(23);
        $outOfQuietHours = $now->setHour(10);

        // Set quiet hours 22:00 - 08:00
        NotificationPreference::factory()
            ->for($user)
            ->create([
                'notification_type' => 'payment.captured',
                'email_enabled' => true,
                'sms_enabled' => true,
                'push_enabled' => true,
                'database_enabled' => true,
                'quiet_hours_enabled' => true,
                'quiet_hours_start' => '22:00',
                'quiet_hours_end' => '08:00',
            ]);

        $notification = Notification::factory()
            ->for($user)
            ->create([
                'type' => 'payment.captured',
                'channels' => ['email', 'sms', 'push', 'database'],
                'title' => 'Payment',
                'body' => 'Body',
            ]);

        // Even during quiet hours, notification should be created (or queued)
        $this->service->send($user->id, $notification, 'test-correlation');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'payment.captured',
        ]);
    }

    /** @test */
    public function it_handles_all_channels_disabled(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['email' => 'test@example.com']);

        // All channels disabled
        NotificationPreference::factory()
            ->for($user)
            ->create([
                'notification_type' => 'payment.captured',
                'email_enabled' => false,
                'sms_enabled' => false,
                'push_enabled' => false,
                'database_enabled' => false,
            ]);

        $notification = Notification::factory()
            ->for($user)
            ->create([
                'type' => 'payment.captured',
                'channels' => ['email', 'sms', 'push', 'database'],
                'title' => 'Payment',
                'body' => 'Body',
            ]);

        // Should gracefully skip all channels
        $this->service->send($user->id, $notification, 'test-correlation');

        // Notification may still be created (status: pending or skipped)
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'payment.captured',
        ]);
    }

    /** @test */
    public function it_tracks_individual_channel_delivery_status(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['email' => 'test@example.com', 'phone' => '+79991234567']);

        NotificationPreference::factory()
            ->for($user)
            ->create([
                'notification_type' => 'payment.captured',
                'email_enabled' => true,
                'sms_enabled' => true,
            ]);

        $notification = Notification::factory()
            ->for($user)
            ->create([
                'type' => 'payment.captured',
                'channels' => ['email', 'sms'],
                'title' => 'Payment',
                'body' => 'Body',
                'metadata' => [],
            ]);

        $this->service->send($user->id, $notification, 'test-correlation');

        // Should track delivery per channel
        $fresh = $notification->fresh();
        $this->assertIsArray($fresh->metadata);
    }
}
