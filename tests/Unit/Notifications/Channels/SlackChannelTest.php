<?php declare(strict_types=1);

namespace Tests\Unit\Notifications\Channels;

use Tests\TestCase;
use App\Notifications\Channels\SlackChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Psr\Log\LoggerInterface;
use Mockery;

/**
 * Тесты SlackChannel — отправка уведомлений через Slack Webhooks.
 *
 * Канон CatVRF 2026.
 */
final class SlackChannelTest extends TestCase
{
    private SlackChannel $channel;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->logger->shouldReceive('info', 'debug', 'warning', 'error')->andReturnNull();

        $this->channel = new SlackChannel(
            logger: $this->logger,
        );
    }

    /** @test */
    public function it_sends_notification_with_to_slack_method(): void
    {
        Http::fake([
            'hooks.slack.com/*' => Http::response('ok', 200),
        ]);

        config([
            'notifications.channels.slack.webhook_url' => 'https://hooks.slack.com/test-webhook',
            'notifications.channels.slack.channel'     => '#general',
            'notifications.channels.slack.username'    => 'CatVRF Bot',
        ]);

        $notifiable = new class { public int $id = 1; };
        $notification = new class extends Notification {
            public function toSlack(): array
            {
                return [
                    'text'    => 'Test Slack message',
                    'channel' => '#alerts',
                ];
            }
            public function getType(): string { return 'test_slack'; }
            public function getCorrelationId(): string { return 'corr-slack-1'; }
            public function getTenantId(): int { return 1; }
        };

        $this->channel->send($notifiable, $notification);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'hooks.slack.com/test-webhook')
                && $request['text'] === 'Test Slack message'
                && $request['channel'] === '#alerts';
        });
    }

    /** @test */
    public function it_skips_when_no_to_slack_method(): void
    {
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->logger->shouldReceive('warning')->once();
        $this->logger->shouldReceive('info', 'debug', 'error')->andReturnNull();

        $channel = new SlackChannel(logger: $this->logger);

        $notifiable = new class { public int $id = 1; };
        $notification = new class extends Notification {};

        $channel->send($notifiable, $notification);
    }

    /** @test */
    public function it_throws_when_no_webhook_url(): void
    {
        config(['notifications.channels.slack.webhook_url' => '']);

        $notifiable = new class { public int $id = 2; };
        $notification = new class extends Notification {
            public function toSlack(): array
            {
                return ['text' => 'no webhook'];
            }
        };

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('webhook URL is not configured');

        $this->channel->send($notifiable, $notification);
    }

    /** @test */
    public function it_throws_when_webhook_returns_error(): void
    {
        Http::fake([
            'hooks.slack.com/*' => Http::response('invalid_payload', 400),
        ]);

        config(['notifications.channels.slack.webhook_url' => 'https://hooks.slack.com/err']);

        $notifiable = new class { public int $id = 3; };
        $notification = new class extends Notification {
            public function toSlack(): array
            {
                return ['text' => 'error test'];
            }
        };

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Slack webhook error');

        $this->channel->send($notifiable, $notification);
    }

    /** @test */
    public function it_sends_slack_blocks(): void
    {
        Http::fake([
            'hooks.slack.com/*' => Http::response('ok', 200),
        ]);

        config(['notifications.channels.slack.webhook_url' => 'https://hooks.slack.com/blocks']);

        $notifiable = new class { public int $id = 4; };
        $notification = new class extends Notification {
            public function toSlack(): array
            {
                return [
                    'text'   => 'fallback text',
                    'blocks' => [
                        ['type' => 'section', 'text' => ['type' => 'mrkdwn', 'text' => '*Bold*']],
                    ],
                ];
            }
        };

        $this->channel->send($notifiable, $notification);

        Http::assertSent(function ($request) {
            return isset($request['blocks'])
                && $request['blocks'][0]['type'] === 'section';
        });
    }

    /** @test */
    public function it_sends_direct_message(): void
    {
        Http::fake([
            'hooks.slack.com/*' => Http::response('ok', 200),
        ]);

        config([
            'notifications.channels.slack.webhook_url' => 'https://hooks.slack.com/direct',
            'notifications.channels.slack.channel'     => '#default',
            'notifications.channels.slack.username'    => 'CatVRF',
        ]);

        $this->channel->sendDirect('Direct alert text', '#security', 'corr-direct-slack');

        Http::assertSent(function ($request) {
            return $request['text'] === 'Direct alert text'
                && $request['channel'] === '#security'
                && $request['username'] === 'CatVRF';
        });
    }

    /** @test */
    public function it_uses_default_channel_for_direct(): void
    {
        Http::fake([
            'hooks.slack.com/*' => Http::response('ok', 200),
        ]);

        config([
            'notifications.channels.slack.webhook_url' => 'https://hooks.slack.com/def',
            'notifications.channels.slack.channel'     => '#monitoring',
        ]);

        $this->channel->sendDirect('Default channel msg');

        Http::assertSent(fn ($req) => $req['channel'] === '#monitoring');
    }

    /** @test */
    public function it_skips_direct_when_no_webhook(): void
    {
        config(['notifications.channels.slack.webhook_url' => '']);

        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->logger->shouldReceive('warning')->once();
        $this->logger->shouldReceive('info', 'debug', 'error')->andReturnNull();

        $channel = new SlackChannel(logger: $this->logger);
        $channel->sendDirect('text', null, 'corr-no-hook');

        Http::assertNothingSent();
    }
}
