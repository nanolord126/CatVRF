<?php declare(strict_types=1);

namespace Tests\Unit\Notifications\Channels;

use Tests\TestCase;
use App\Notifications\Channels\MarketplaceChannel;
use App\Notifications\Channels\InAppChannel;
use App\Domains\Education\Channels\Models\BusinessChannel;
use App\Domains\Education\Channels\Models\ChannelSubscriber;
use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use Mockery;
use Psr\Log\LoggerInterface;

/**
 * Тесты MarketplaceChannel — внутренних каналов/пабликов маркетплейса.
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Покрытие:
 *  - send() — через Notification-объект с toMarketplace()
 *  - sendDirect() — прямая отправка по tenantId
 *  - sendToSubscriber() — персональная отправка подписчику
 *  - Обработка отсутствующего канала бизнеса
 *  - Обработка отсутствующего toMarketplace()
 *  - createChannelPost() — создание поста
 *  - notifySubscribers() — рассылка in-app
 *  - Обработка исключений при нотификации подписчиков
 */
final class MarketplaceChannelTest extends TestCase
{
    use WithFaker;

    private MarketplaceChannel $channel;
    private LoggerInterface $logger;
    private DatabaseManager $db;
    private InAppChannel $inAppChannel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->logger->shouldReceive('info', 'debug', 'warning', 'error')->andReturnNull();

        $this->db = Mockery::mock(DatabaseManager::class);
        $this->inAppChannel = Mockery::mock(InAppChannel::class);

        $this->channel = new MarketplaceChannel(
            logger:      $this->logger,
            db:          $this->db,
            inAppChannel: $this->inAppChannel,
        );
    }

    /** @test */
    public function it_warns_when_notification_has_no_toMarketplace(): void
    {
        $notifiable = $this->createNotifiable();
        $notification = new class extends Notification {
            public function via($notifiable): array
            {
                return ['marketplace'];
            }
        };

        $this->logger->shouldReceive('warning')
            ->once()
            ->with('Notification does not have toMarketplace method', Mockery::type('array'));

        $this->channel->send($notifiable, $notification);
    }

    /** @test */
    public function it_throws_when_tenant_id_missing(): void
    {
        $notifiable = $this->createNotifiable();
        $notification = new class extends Notification {
            public function via($notifiable): array
            {
                return ['marketplace'];
            }

            public function toMarketplace(): array
            {
                return [
                    'title'   => 'Test',
                    'content' => 'No tenant',
                    // tenant_id отсутствует
                ];
            }
        };

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Marketplace channel requires tenant_id');

        $this->channel->send($notifiable, $notification);
    }

    /** @test */
    public function it_skips_send_when_no_active_channel(): void
    {
        $this->mockBusinessChannelQuery(tenantId: 42, returnChannel: null);

        $notifiable = $this->createNotifiable();
        $notification = $this->createMarketplaceNotification(tenantId: 42);

        $this->logger->shouldReceive('info')
            ->once()
            ->with('No active marketplace channel for tenant, skipping', Mockery::type('array'));

        $this->channel->send($notifiable, $notification);
    }

    /** @test */
    public function it_creates_post_and_notifies_subscribers_on_send(): void
    {
        $businessChannel = $this->createBusinessChannelMock(channelId: 10, tenantId: 5);
        $this->mockBusinessChannelQuery(tenantId: 5, returnChannel: $businessChannel);

        // Mock создания поста
        $this->db->shouldReceive('table')
            ->with('posts')
            ->andReturnSelf();
        $this->db->shouldReceive('insertGetId')
            ->once()
            ->andReturn(99);

        // Mock подписчиков
        $this->mockSubscribersQuery(channelId: 10, userIds: [100, 200, 300]);

        // Каждому подписчику отправляется in-app
        $this->inAppChannel->shouldReceive('sendDirect')
            ->times(3);

        // increment и update на канале
        $businessChannel->shouldReceive('increment')->with('posts_count')->once();
        $businessChannel->shouldReceive('update')->once();

        $notifiable = $this->createNotifiable();
        $notification = $this->createMarketplaceNotification(tenantId: 5);

        $this->logger->shouldReceive('info')
            ->once()
            ->with('Marketplace channel notification sent', Mockery::on(function ($ctx) {
                return $ctx['tenant_id'] === 5
                    && $ctx['channel_id'] === 10
                    && $ctx['post_id'] === 99
                    && $ctx['subscribers_sent'] === 3;
            }));

        $this->channel->send($notifiable, $notification);
    }

    /** @test */
    public function it_skips_direct_send_when_no_channel(): void
    {
        $this->mockBusinessChannelQuery(tenantId: 7, returnChannel: null);

        $this->logger->shouldReceive('debug')
            ->once()
            ->with('No marketplace channel for tenant, skipping direct send', Mockery::type('array'));

        $this->channel->sendDirect(
            tenantId: 7,
            title: 'Alert',
            message: 'Test',
        );
    }

    /** @test */
    public function it_creates_post_on_direct_send(): void
    {
        $businessChannel = $this->createBusinessChannelMock(channelId: 15, tenantId: 8);
        $this->mockBusinessChannelQuery(tenantId: 8, returnChannel: $businessChannel);

        $this->db->shouldReceive('table')
            ->with('posts')
            ->andReturnSelf();
        $this->db->shouldReceive('insertGetId')
            ->once()
            ->andReturn(42);

        $this->mockSubscribersQuery(channelId: 15, userIds: [500]);

        $this->inAppChannel->shouldReceive('sendDirect')->once();

        $businessChannel->shouldReceive('increment')->with('posts_count')->once();
        $businessChannel->shouldReceive('update')->once();

        $this->logger->shouldReceive('info')
            ->once()
            ->with('Marketplace direct notification sent', Mockery::on(function ($ctx) {
                return $ctx['tenant_id'] === 8
                    && $ctx['post_id'] === 42
                    && $ctx['subscribers_sent'] === 1;
            }));

        $this->channel->sendDirect(
            tenantId: 8,
            title: 'Direct Alert',
            message: 'Direct message',
            correlationId: 'test-corr-direct',
        );
    }

    /** @test */
    public function it_sends_to_specific_subscriber(): void
    {
        $this->inAppChannel->shouldReceive('sendDirect')
            ->once()
            ->with(
                Mockery::on(fn ($v) => $v === 123),       // userId
                Mockery::on(fn ($v) => $v === 'Alert'),    // title
                Mockery::on(fn ($v) => $v === 'Body'),     // message
                Mockery::on(fn ($v) => $v === 'marketplace'), // type
                Mockery::any(),                             // correlationId
                Mockery::on(fn ($v) => $v === 5),           // tenantId
            );

        $this->channel->sendToSubscriber(
            userId: 123,
            tenantId: 5,
            title: 'Alert',
            message: 'Body',
            correlationId: 'sub-corr',
        );
    }

    /** @test */
    public function it_handles_subscriber_notification_failure_gracefully(): void
    {
        $businessChannel = $this->createBusinessChannelMock(channelId: 20, tenantId: 3);
        $this->mockBusinessChannelQuery(tenantId: 3, returnChannel: $businessChannel);

        $this->db->shouldReceive('table')->with('posts')->andReturnSelf();
        $this->db->shouldReceive('insertGetId')->andReturn(55);

        $this->mockSubscribersQuery(channelId: 20, userIds: [10, 20]);

        $businessChannel->shouldReceive('increment')->with('posts_count')->once();
        $businessChannel->shouldReceive('update')->once();

        // Первый подписчик — ошибка, второй — ОК
        $this->inAppChannel->shouldReceive('sendDirect')
            ->once()
            ->andThrow(new \RuntimeException('Connection failed'));
        $this->inAppChannel->shouldReceive('sendDirect')
            ->once();

        $this->logger->shouldReceive('warning')
            ->once()
            ->with('Failed to notify subscriber', Mockery::type('array'));

        $this->logger->shouldReceive('info')
            ->once()
            ->with('Marketplace direct notification sent', Mockery::on(fn ($ctx) => $ctx['subscribers_sent'] === 1));

        $this->channel->sendDirect(
            tenantId: 3,
            title: 'Partial',
            message: 'Some fail',
        );
    }

    /** @test */
    public function it_creates_promo_post_when_flag_set(): void
    {
        $businessChannel = $this->createBusinessChannelMock(channelId: 25, tenantId: 9);
        $this->mockBusinessChannelQuery(tenantId: 9, returnChannel: $businessChannel);

        $this->db->shouldReceive('table')->with('posts')->andReturnSelf();
        $this->db->shouldReceive('insertGetId')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['is_promo'] === true;
            }))
            ->andReturn(66);

        $this->mockSubscribersQuery(channelId: 25, userIds: []);

        $businessChannel->shouldReceive('increment')->with('posts_count')->once();
        $businessChannel->shouldReceive('update')->once();

        $this->channel->sendDirect(
            tenantId: 9,
            title: 'Promo!',
            message: 'Buy now!',
            isPromo: true,
        );
    }

    /** @test */
    public function it_notifies_zero_subscribers_without_errors(): void
    {
        $businessChannel = $this->createBusinessChannelMock(channelId: 30, tenantId: 11);
        $this->mockBusinessChannelQuery(tenantId: 11, returnChannel: $businessChannel);

        $this->db->shouldReceive('table')->with('posts')->andReturnSelf();
        $this->db->shouldReceive('insertGetId')->andReturn(77);

        $this->mockSubscribersQuery(channelId: 30, userIds: []);

        $businessChannel->shouldReceive('increment')->with('posts_count')->once();
        $businessChannel->shouldReceive('update')->once();

        $this->inAppChannel->shouldNotReceive('sendDirect');

        $this->logger->shouldReceive('info')
            ->once()
            ->with('Marketplace direct notification sent', Mockery::on(fn ($ctx) => $ctx['subscribers_sent'] === 0));

        $this->channel->sendDirect(
            tenantId: 11,
            title: 'Empty channel',
            message: 'No subscribers',
        );
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

    private function createMarketplaceNotification(int $tenantId = 1): Notification
    {
        return new class($tenantId) extends Notification {
            public function __construct(private readonly int $tenantId) {}

            public function via($notifiable): array
            {
                return ['marketplace'];
            }

            public function toMarketplace(): array
            {
                return [
                    'title'     => 'Test marketplace notification',
                    'content'   => 'This is a test notification via marketplace channel.',
                    'tenant_id' => $this->tenantId,
                    'is_promo'  => false,
                ];
            }

            public function getCorrelationId(): string
            {
                return 'mkt-test-corr-' . $this->tenantId;
            }

            public function getTenantId(): int
            {
                return $this->tenantId;
            }

            public function getType(): string
            {
                return 'marketplace_test';
            }
        };
    }

    private function createBusinessChannelMock(int $channelId, int $tenantId): Mockery\MockInterface
    {
        $mock = Mockery::mock(BusinessChannel::class)->makePartial();
        $mock->id = $channelId;
        $mock->tenant_id = $tenantId;
        $mock->status = 'active';

        return $mock;
    }

    /**
     * @param int                          $tenantId
     * @param BusinessChannel|null $returnChannel
     */
    private function mockBusinessChannelQuery(int $tenantId, ?object $returnChannel): void
    {
        $queryMock = Mockery::mock();
        $queryMock->shouldReceive('where')
            ->with('tenant_id', $tenantId)
            ->andReturnSelf();
        $queryMock->shouldReceive('where')
            ->with('status', 'active')
            ->andReturnSelf();
        $queryMock->shouldReceive('first')
            ->andReturn($returnChannel);

        // Мок BusinessChannel::withoutGlobalScopes()
        BusinessChannel::shouldReceive('withoutGlobalScopes')
            ->andReturn($queryMock);
    }

    /**
     * @param int        $channelId
     * @param array<int> $userIds
     */
    private function mockSubscribersQuery(int $channelId, array $userIds): void
    {
        $queryMock = Mockery::mock();
        $queryMock->shouldReceive('where')
            ->with('channel_id', $channelId)
            ->andReturnSelf();
        $queryMock->shouldReceive('whereNull')
            ->with('unsubscribed_at')
            ->andReturnSelf();
        $queryMock->shouldReceive('pluck')
            ->with('user_id')
            ->andReturn(collect($userIds));

        ChannelSubscriber::shouldReceive('withoutGlobalScopes')
            ->andReturn($queryMock);
    }
}
