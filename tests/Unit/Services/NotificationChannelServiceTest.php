<?php declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\NotificationChannelService;
use App\Services\NotificationPreferencesService;
use App\Notifications\Channels\EmailChannel;
use App\Notifications\Channels\SmsChannel;
use App\Notifications\Channels\PushChannel;
use App\Notifications\Channels\MarketplaceChannel;
use App\Notifications\Channels\SlackChannel;
use App\Notifications\Channels\InAppChannel;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Notifications\Notification;
use Mockery;
use Psr\Log\LoggerInterface;

/**
 * Тесты NotificationChannelService — единого роутера каналов.
 *
 * Канон CatVRF 2026.
 * Покрытие: роутинг, DND, preferences, rate-limit, multi-channel, direct send.
 */
final class NotificationChannelServiceTest extends TestCase
{
    use WithFaker;

    private NotificationChannelService $service;
    private EmailChannel $emailChannel;
    private SmsChannel $smsChannel;
    private PushChannel $pushChannel;
    private MarketplaceChannel $marketplaceChannel;
    private SlackChannel $slackChannel;
    private InAppChannel $inAppChannel;
    private NotificationPreferencesService $preferencesService;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->emailChannel = Mockery::mock(EmailChannel::class);
        $this->smsChannel = Mockery::mock(SmsChannel::class);
        $this->pushChannel = Mockery::mock(PushChannel::class);
        $this->marketplaceChannel = Mockery::mock(MarketplaceChannel::class);
        $this->slackChannel = Mockery::mock(SlackChannel::class);
        $this->inAppChannel = Mockery::mock(InAppChannel::class);
        $this->preferencesService = Mockery::mock(NotificationPreferencesService::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->logger->shouldReceive('info', 'debug', 'warning', 'error')->andReturnNull();

        $this->service = new NotificationChannelService(
            emailChannel: $this->emailChannel,
            smsChannel: $this->smsChannel,
            pushChannel: $this->pushChannel,
            marketplaceChannel: $this->marketplaceChannel,
            slackChannel: $this->slackChannel,
            inAppChannel: $this->inAppChannel,
            preferencesService: $this->preferencesService,
            logger: $this->logger,
        );
    }

    /** @test */
    public function it_returns_all_available_channels(): void
    {
        $channels = $this->service->getAvailableChannels();

        $this->assertCount(6, $channels);
        $this->assertContains('email', $channels);
        $this->assertContains('sms', $channels);
        $this->assertContains('push', $channels);
        $this->assertContains('marketplace', $channels);
        $this->assertContains('slack', $channels);
        $this->assertContains('in_app', $channels);
    }

    /** @test */
    public function it_returns_only_enabled_channels(): void
    {
        config([
            'notifications.channels.email.enabled' => true,
            'notifications.channels.sms.enabled' => false,
            'notifications.channels.push.enabled' => true,
            'notifications.channels.marketplace.enabled' => false,
            'notifications.channels.slack.enabled' => false,
            'notifications.channels.in_app.enabled' => true,
        ]);

        $enabled = $this->service->getEnabledChannels();

        $this->assertContains('email', $enabled);
        $this->assertContains('push', $enabled);
        $this->assertContains('in_app', $enabled);
        $this->assertNotContains('sms', $enabled);
        $this->assertNotContains('marketplace', $enabled);
        $this->assertNotContains('slack', $enabled);
    }

    /** @test */
    public function it_rejects_invalid_channel(): void
    {
        $notifiable = $this->createNotifiable();
        $notification = $this->createNotification();

        $result = $this->service->send(
            channel: 'carrier_pigeon',
            notifiable: $notifiable,
            notification: $notification,
        );

        $this->assertFalse($result);
    }

    /** @test */
    public function it_rejects_disabled_channel(): void
    {
        config(['notifications.channels.sms.enabled' => false]);

        $notifiable = $this->createNotifiable();
        $notification = $this->createNotification();

        $result = $this->service->send(
            channel: 'sms',
            notifiable: $notifiable,
            notification: $notification,
        );

        $this->assertFalse($result);
    }

    /** @test */
    public function it_sends_via_email_channel(): void
    {
        config(['notifications.channels.email.enabled' => true]);
        $this->preferencesService->shouldReceive('getPreferences')
            ->andReturn(['email' => ['enabled' => true]]);

        $notifiable = $this->createNotifiable();
        $notification = $this->createNotification();

        $this->emailChannel->shouldReceive('send')
            ->once()
            ->with($notifiable, $notification);

        $result = $this->service->send(
            channel: 'email',
            notifiable: $notifiable,
            notification: $notification,
            correlationId: 'test-corr-id',
        );

        $this->assertTrue($result);
    }

    /** @test */
    public function it_sends_via_in_app_channel(): void
    {
        config(['notifications.channels.in_app.enabled' => true]);
        $this->preferencesService->shouldReceive('getPreferences')
            ->andReturn(['in_app' => ['enabled' => true]]);

        $notifiable = $this->createNotifiable();
        $notification = $this->createNotification();

        $this->inAppChannel->shouldReceive('send')
            ->once()
            ->with($notifiable, $notification);

        $result = $this->service->send(
            channel: 'in_app',
            notifiable: $notifiable,
            notification: $notification,
        );

        $this->assertTrue($result);
    }

    /** @test */
    public function it_sends_via_marketplace_channel(): void
    {
        config(['notifications.channels.marketplace.enabled' => true]);
        $this->preferencesService->shouldReceive('getPreferences')
            ->andReturn(['marketplace' => ['enabled' => true]]);

        $notifiable = $this->createNotifiable();
        $notification = $this->createNotification();

        $this->marketplaceChannel->shouldReceive('send')
            ->once()
            ->with($notifiable, $notification);

        $result = $this->service->send(
            channel: 'marketplace',
            notifiable: $notifiable,
            notification: $notification,
        );

        $this->assertTrue($result);
    }

    /** @test */
    public function it_sends_via_slack_channel(): void
    {
        config(['notifications.channels.slack.enabled' => true]);
        $this->preferencesService->shouldReceive('getPreferences')
            ->andReturn(['slack' => ['enabled' => true]]);

        $notifiable = $this->createNotifiable();
        $notification = $this->createNotification();

        $this->slackChannel->shouldReceive('send')
            ->once()
            ->with($notifiable, $notification);

        $result = $this->service->send(
            channel: 'slack',
            notifiable: $notifiable,
            notification: $notification,
        );

        $this->assertTrue($result);
    }

    /** @test */
    public function it_blocks_when_user_disabled_channel(): void
    {
        config(['notifications.channels.email.enabled' => true]);
        $this->preferencesService->shouldReceive('getPreferences')
            ->andReturn(['email' => ['enabled' => false]]);

        $notifiable = $this->createNotifiable();
        $notification = $this->createNotification();

        $result = $this->service->send(
            channel: 'email',
            notifiable: $notifiable,
            notification: $notification,
        );

        $this->assertFalse($result);
    }

    /** @test */
    public function it_skips_preferences_when_flag_set(): void
    {
        config(['notifications.channels.email.enabled' => true]);
        $this->preferencesService->shouldReceive('getPreferences')
            ->andReturn(['email' => ['enabled' => false]]);

        $notifiable = $this->createNotifiable();
        $notification = $this->createNotification();

        $this->emailChannel->shouldReceive('send')->once();

        $result = $this->service->send(
            channel: 'email',
            notifiable: $notifiable,
            notification: $notification,
            skipPreferences: true,
        );

        $this->assertTrue($result);
    }

    /** @test */
    public function it_blocks_notification_during_dnd(): void
    {
        config([
            'notifications.channels.email.enabled' => true,
            'notifications.dnd.enabled' => true,
            'notifications.dnd.bypass_channels' => ['sms'],
        ]);

        $notifiable = $this->createNotifiable(42);

        // Устанавливаем DND в кэш — текущее время попадает в окно
        $now = now();
        $start = $now->copy()->subHour()->format('H:i');
        $end = $now->copy()->addHour()->format('H:i');
        cache()->put("dnd:user.42.enabled", true, 3600);
        cache()->put("dnd:user.42.start_time", $start, 3600);
        cache()->put("dnd:user.42.end_time", $end, 3600);

        $notification = $this->createNotification();

        $result = $this->service->send(
            channel: 'email',
            notifiable: $notifiable,
            notification: $notification,
        );

        $this->assertFalse($result);

        // Чистим кэш
        cache()->forget("dnd:user.42.enabled");
        cache()->forget("dnd:user.42.start_time");
        cache()->forget("dnd:user.42.end_time");
    }

    /** @test */
    public function it_bypasses_dnd_for_sms(): void
    {
        config([
            'notifications.channels.sms.enabled' => true,
            'notifications.dnd.enabled' => true,
            'notifications.dnd.bypass_channels' => ['sms'],
        ]);

        $notifiable = $this->createNotifiable(42);

        $now = now();
        cache()->put("dnd:user.42.enabled", true, 3600);
        cache()->put("dnd:user.42.start_time", $now->copy()->subHour()->format('H:i'), 3600);
        cache()->put("dnd:user.42.end_time", $now->copy()->addHour()->format('H:i'), 3600);

        $this->preferencesService->shouldReceive('getPreferences')
            ->andReturn(['sms' => ['enabled' => true]]);

        $notification = $this->createNotification();
        $this->smsChannel->shouldReceive('send')->once();

        $result = $this->service->send(
            channel: 'sms',
            notifiable: $notifiable,
            notification: $notification,
        );

        $this->assertTrue($result);

        cache()->forget("dnd:user.42.enabled");
        cache()->forget("dnd:user.42.start_time");
        cache()->forget("dnd:user.42.end_time");
    }

    /** @test */
    public function it_skips_dnd_when_flag_set(): void
    {
        config([
            'notifications.channels.email.enabled' => true,
            'notifications.dnd.enabled' => true,
        ]);

        $notifiable = $this->createNotifiable(42);

        $now = now();
        cache()->put("dnd:user.42.enabled", true, 3600);
        cache()->put("dnd:user.42.start_time", $now->copy()->subHour()->format('H:i'), 3600);
        cache()->put("dnd:user.42.end_time", $now->copy()->addHour()->format('H:i'), 3600);

        $this->preferencesService->shouldReceive('getPreferences')
            ->andReturn(['email' => ['enabled' => true]]);

        $notification = $this->createNotification();
        $this->emailChannel->shouldReceive('send')->once();

        $result = $this->service->send(
            channel: 'email',
            notifiable: $notifiable,
            notification: $notification,
            skipDnd: true,
        );

        $this->assertTrue($result);

        cache()->forget("dnd:user.42.enabled");
        cache()->forget("dnd:user.42.start_time");
        cache()->forget("dnd:user.42.end_time");
    }

    /** @test */
    public function it_sends_to_multiple_channels(): void
    {
        config([
            'notifications.channels.email.enabled' => true,
            'notifications.channels.push.enabled' => true,
            'notifications.channels.in_app.enabled' => true,
        ]);

        $this->preferencesService->shouldReceive('getPreferences')
            ->andReturn([
                'email'  => ['enabled' => true],
                'push'   => ['enabled' => true],
                'in_app' => ['enabled' => true],
            ]);

        $notifiable = $this->createNotifiable();
        $notification = $this->createNotification();

        $this->emailChannel->shouldReceive('send')->once();
        $this->pushChannel->shouldReceive('send')->once();
        $this->inAppChannel->shouldReceive('send')->once();

        $results = $this->service->sendToChannels(
            channels: ['email', 'push', 'in_app'],
            notifiable: $notifiable,
            notification: $notification,
            correlationId: 'multi-test-corr',
        );

        $this->assertCount(3, $results);
        $this->assertTrue($results['email']);
        $this->assertTrue($results['push']);
        $this->assertTrue($results['in_app']);
    }

    /** @test */
    public function it_sends_direct_in_app_notification(): void
    {
        config(['notifications.channels.in_app.enabled' => true]);

        $this->inAppChannel->shouldReceive('sendDirect')
            ->once()
            ->with(
                Mockery::on(fn ($uid) => $uid === 1),
                Mockery::on(fn ($title) => $title === 'Test Title'),
                Mockery::on(fn ($msg) => $msg === 'Test message'),
                Mockery::any(),
                Mockery::any(),
            );

        $result = $this->service->sendDirect(
            channel: 'in_app',
            userId: 1,
            title: 'Test Title',
            message: 'Test message',
        );

        $this->assertTrue($result);
    }

    /** @test */
    public function it_sends_direct_to_multiple_channels(): void
    {
        config([
            'notifications.channels.in_app.enabled' => true,
            'notifications.channels.slack.enabled' => true,
        ]);

        $this->inAppChannel->shouldReceive('sendDirect')->once();
        $this->slackChannel->shouldReceive('sendDirect')->once();

        $results = $this->service->sendDirectToChannels(
            channels: ['in_app', 'slack'],
            userId: 1,
            title: 'Alert',
            message: 'Something happened',
            correlationId: 'direct-multi-test',
        );

        $this->assertCount(2, $results);
        $this->assertTrue($results['in_app']);
        $this->assertTrue($results['slack']);
    }

    /** @test */
    public function it_rate_limits_notifications(): void
    {
        config([
            'notifications.channels.email.enabled' => true,
            'notifications.channels.email.rate_limit.per_user_per_hour' => 2,
        ]);

        $this->preferencesService->shouldReceive('getPreferences')
            ->andReturn(['email' => ['enabled' => true]]);

        $notifiable = $this->createNotifiable(99);
        $notification = $this->createNotification();

        $this->emailChannel->shouldReceive('send')->twice();

        // Первые два проходят
        $this->service->send('email', $notifiable, $notification);
        $this->service->send('email', $notifiable, $notification);

        // Третий блокируется
        $result = $this->service->send('email', $notifiable, $notification);
        $this->assertFalse($result);

        cache()->forget("notif_rate:email:99");
    }

    /** @test */
    public function it_handles_channel_exception_gracefully(): void
    {
        config(['notifications.channels.email.enabled' => true]);
        $this->preferencesService->shouldReceive('getPreferences')
            ->andReturn(['email' => ['enabled' => true]]);

        $notifiable = $this->createNotifiable();
        $notification = $this->createNotification();

        $this->emailChannel->shouldReceive('send')
            ->once()
            ->andThrow(new \RuntimeException('SMTP connection failed'));

        $result = $this->service->send(
            channel: 'email',
            notifiable: $notifiable,
            notification: $notification,
        );

        $this->assertFalse($result);
    }

    // ══════════════════════════════════════════════
    //  Helpers
    // ══════════════════════════════════════════════

    private function createNotifiable(int $id = 1): object
    {
        return new class($id) {
            public function __construct(public readonly int $id) {}
        };
    }

    private function createNotification(): Notification
    {
        return new class extends Notification {
            public function getCorrelationId(): string
            {
                return 'test-correlation-id';
            }

            public function getTenantId(): ?int
            {
                return 1;
            }

            public function getType(): string
            {
                return 'test_notification';
            }

            public function via($notifiable): array
            {
                return ['email'];
            }
        };
    }
}
