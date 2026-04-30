<?php

declare(strict_types=1);

namespace App\Domains\Shared\Notifications\Services;

use App\Models\UserWeChatLink;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final readonly class WeChatService
{
    private string $appId;
    private string $appSecret;
    private string $apiUrl;

    public function __construct()
    {
        $this->appId = config('services.wechat.app_id', '');
        $this->appSecret = config('services.wechat.app_secret', '');
        $this->apiUrl = config('services.wechat.api_url', 'https://api.weixin.qq.com/cgi-bin');
    }

    /**
     * Send order notification via WeChat
     */
    public function sendOrderNotification(
        int $orderId,
        int $buyerId,
        int $sellerId,
        string $eventType,
        array $data = []
    ): void {
        $links = UserWeChatLink::whereIn('user_id', [$buyerId, $sellerId])
            ->active()
            ->verified()
            ->get();

        foreach ($links as $link) {
            $recipientType = $link->user_id === $buyerId ? 'buyer' : 'seller';
            $message = $this->buildOrderMessage($orderId, $eventType, $recipientType, $data);

            try {
                $this->sendMessage($link->wechat_openid, $message);
                $link->markAsNotified();
            } catch (\Throwable $e) {
                Log::error('Failed to send WeChat notification', [
                    'wechat_openid' => $link->wechat_openid,
                    'order_id' => $orderId,
                    'event' => $eventType,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Send template message via WeChat
     */
    public function sendMessage(string $openid, array $data): bool
    {
        $accessToken = $this->getAccessToken();

        $response = Http::post("{$this->apiUrl}/message/template/send?access_token={$accessToken}", [
            'touser' => $openid,
            'template_id' => config('services.wechat.template_id', ''),
            'data' => $data,
        ]);

        if (!$response->successful()) {
            Log::error('WeChat API error', [
                'response' => $response->body(),
            ]);
            return false;
        }

        return true;
    }

    /**
     * Send custom message via WeChat
     */
    public function sendCustomMessage(string $openid, string $message): bool
    {
        $accessToken = $this->getAccessToken();

        $response = Http::post("{$this->apiUrl}/message/custom/send?access_token={$accessToken}", [
            'touser' => $openid,
            'msgtype' => 'text',
            'text' => [
                'content' => $message,
            ],
        ]);

        return $response->successful();
    }

    private function getAccessToken(): string
    {
        $response = Http::get("{$this->apiUrl}/token", [
            'grant_type' => 'client_credential',
            'appid' => $this->appId,
            'secret' => $this->appSecret,
        ]);

        if (!$response->successful()) {
            Log::error('WeChat access token error', [
                'response' => $response->body(),
            ]);
            return '';
        }

        return $response->json('access_token', '');
    }

    public function sendNotificationToUser(int $userId, string $message): bool
    {
        $link = UserWeChatLink::where('user_id', $userId)
            ->active()
            ->verified()
            ->first();

        if (!$link) {
            return false;
        }

        $result = $this->sendCustomMessage($link->wechat_openid, $message);

        if ($result) {
            $link->markAsNotified();
        }

        return $result;
    }

    public function linkAccount(int $userId, string $openid, string $unionid = null): ?UserWeChatLink
    {
        $link = UserWeChatLink::firstOrCreate(
            ['user_id' => $userId],
            ['wechat_openid' => $openid, 'wechat_unionid' => $unionid, 'is_active' => false]
        );

        $link->verify();
        return $link;
    }

    public function unlinkAccount(int $userId): bool
    {
        $link = UserWeChatLink::where('user_id', $userId)->first();

        if (!$link) {
            return false;
        }

        $link->unlink();
        return true;
    }

    /**
     * Build interactive message payload for order notifications
     */
    private function buildInteractivePayload(
        int $orderId,
        string $eventType,
        bool $isBuyer,
        array $data
    ): array {
        $trackingUrl = "https://catvrf.ru/orders/{$orderId}";

        return match($eventType) {
            'created' => $isBuyer ? [
                'header' => '订单已创建',
                'body' => "✅ 您的订单 #{$orderId} 已创建!\n金额: " . ($data['amount'] ?? '0') . " ₽",
                'buttons' => [
                    ['id' => "track_{$orderId}", 'title' => '📍 追踪订单'],
                    ['id' => "my_orders_{$orderId}", 'title' => '🛍 我的订单'],
                ],
            ] : [
                'header' => '新订单',
                'body' => "🛒 新订单 #{$orderId}\n金额: " . ($data['amount'] ?? '0') . " ₽",
                'buttons' => [
                    ['id' => "accept_{$orderId}", 'title' => '✅ 接受'],
                    ['id' => "reject_{$orderId}", 'title' => '❌ 拒绝'],
                ],
            ],

            'ready_for_delivery' => [
                'header' => '订单准备就绪',
                'body' => "📦 订单 #{$orderId} 准备配送!",
                'buttons' => [
                    ['id' => "start_delivery_{$orderId}", 'title' => '🚚 开始配送'],
                ],
            ],

            'in_delivery' => [
                'header' => '配送中',
                'body' => "🚚 配送中!\n订单 #{$orderId}",
                'buttons' => [
                    ['id' => "track_{$orderId}", 'title' => '📍 地图'],
                ],
            ],

            default => [
                'header' => '订单状态',
                'body' => "订单 #{$orderId}: {$eventType}",
                'buttons' => [
                    ['id' => "track_{$orderId}", 'title' => '📍 追踪'],
                ],
            ],
        };
    }

    /**
     * Send interactive message with buttons
     */
    private function sendInteractiveMessage(string $openid, string $header, string $body, array $buttons): bool
    {
        $accessToken = $this->getAccessToken();

        $response = Http::post("{$this->apiUrl}/message/template/send?access_token={$accessToken}", [
            'touser' => $openid,
            'msgtype' => 'template_card',
            'template_card' => [
                'card_type' => 'text_notice',
                'title' => $header,
                'content' => $body,
                'button_list' => array_map(fn($btn) => [
                    'text' => $btn['title'],
                    'style' => 1,
                    'key' => $btn['id'],
                ], $buttons),
            ],
        ]);

        return $response->successful();
    }

    /**
     * Send interactive order notification with buttons
     */
    public function sendInteractive(
        int $orderId,
        int $buyerId,
        int $sellerId,
        string $eventType,
        array $data = []
    ): void {
        $links = UserWeChatLink::whereIn('user_id', [$buyerId, $sellerId])
            ->active()
            ->verified()
            ->get();

        foreach ($links as $link) {
            $isBuyer = $link->user_id === $buyerId;
            $payload = $this->buildInteractivePayload($orderId, $eventType, $isBuyer, $data);

            try {
                $this->sendInteractiveMessage(
                    $link->wechat_openid,
                    $payload['header'] ?? '',
                    $payload['body'],
                    $payload['buttons']
                );
                $link->markAsNotified();
            } catch (\Throwable $e) {
                Log::error('WeChat interactive message failed, falling back to text', [
                    'wechat_openid' => $link->wechat_openid,
                    'order_id' => $orderId,
                    'event' => $eventType,
                    'error' => $e->getMessage(),
                ]);
                $message = $this->buildOrderMessage($orderId, $eventType, $isBuyer ? 'buyer' : 'seller', $data);
                $this->sendCustomMessage($link->wechat_openid, $message);
                $link->markAsNotified();
            }
        }
    }

    private function buildOrderMessage(
        int $orderId,
        string $eventType,
        string $recipientType,
        array $data
    ): array {
        $trackingUrl = url("/orders/{$orderId}");

        return [
            'first' => ['value' => $eventType === 'created' ? '订单创建成功' : '订单状态更新'],
            'keyword1' => ['value' => (string) $orderId],
            'keyword2' => ['value' => $eventType],
            'keyword3' => ['value' => $data['amount'] ?? '0'],
            'remark' => ['value' => "点击查看详情: {$trackingUrl}"],
        ];
    }
}
