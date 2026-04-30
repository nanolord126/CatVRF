<?php

declare(strict_types=1);

namespace App\Domains\Shared\Notifications\Services;

use App\Models\UserKakaoTalkLink;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final readonly class KakaoTalkService
{
    private string $apiKey;
    private string $apiSecret;
    private string $senderNumber;
    private string $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('services.kakao.api_key', '');
        $this->apiSecret = config('services.kakao.api_secret', '');
        $this->senderNumber = config('services.kakao.sender_number', '');
        $this->apiUrl = config('services.kakao.api_url', 'https://api.kakaotalk.com');
    }

    /**
     * Send order notification via KakaoTalk
     */
    public function sendOrderNotification(
        int $orderId,
        int $buyerId,
        int $sellerId,
        string $eventType,
        array $data = []
    ): void {
        $links = UserKakaoTalkLink::whereIn('user_id', [$buyerId, $sellerId])
            ->active()
            ->verified()
            ->get();

        foreach ($links as $link) {
            $recipientType = $link->user_id === $buyerId ? 'buyer' : 'seller';
            $message = $this->buildOrderMessage($orderId, $eventType, $recipientType, $data);

            try {
                $this->sendMessage($link->kakao_id, $message);
                $link->markAsNotified();
            } catch (\Throwable $e) {
                Log::error('Failed to send KakaoTalk notification', [
                    'kakao_id' => $link->kakao_id,
                    'order_id' => $orderId,
                    'event' => $eventType,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Send message to KakaoTalk user
     */
    public function sendMessage(string $kakaoId, string $message): bool
    {
        $response = Http::post("{$this->apiUrl}/v2/send", [
            'sender_number' => $this->senderNumber,
            'receiver_number' => $kakaoId,
            'message' => $message,
            'type' => 'AT',
        ], [
            'headers' => [
                'Authorization' => "KakaoAK {$this->apiKey}",
                'X-Kakao-Api-Secret' => $this->apiSecret,
                'Content-Type' => 'application/json',
            ],
        ]);

        if (!$response->successful()) {
            Log::error('KakaoTalk API error', [
                'response' => $response->body(),
            ]);
            return false;
        }

        return true;
    }

    /**
     * Send notification to specific user
     */
    public function sendNotificationToUser(int $userId, string $message): bool
    {
        $link = UserKakaoTalkLink::where('user_id', $userId)
            ->active()
            ->verified()
            ->first();

        if (!$link) {
            return false;
        }

        $result = $this->sendMessage($link->kakao_id, $message);

        if ($result) {
            $link->markAsNotified();
        }

        return $result;
    }

    /**
     * Link account via verification token
     */
    public function linkAccount(int $userId, string $kakaoId): ?UserKakaoTalkLink
    {
        $link = UserKakaoTalkLink::firstOrCreate(
            ['user_id' => $userId],
            ['kakao_id' => $kakaoId, 'is_active' => false]
        );

        $link->verify();
        return $link;
    }

    /**
     * Unlink account
     */
    public function unlinkAccount(int $userId): bool
    {
        $link = UserKakaoTalkLink::where('user_id', $userId)->first();

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
                'header' => '주문 완료',
                'body' => "✅ 주문 #{$orderId} 완료!\n금액: " . ($data['amount'] ?? '0') . " ₽",
                'buttons' => [
                    ['label' => '📍 주문 추적', 'action' => "track_{$orderId}"],
                    ['label' => '🛍 내 주문', 'action' => "my_orders_{$orderId}"],
                ],
            ] : [
                'header' => '새 주문',
                'body' => "🛒 새 주문 #{$orderId}\n금액: " . ($data['amount'] ?? '0') . " ₽",
                'buttons' => [
                    ['label' => '✅ 수락', 'action' => "accept_{$orderId}"],
                    ['label' => '❌ 거절', 'action' => "reject_{$orderId}"],
                ],
            ],

            'ready_for_delivery' => [
                'header' => '배송 준비',
                'body' => "📦 주문 #{$orderId} 배송 준비!",
                'buttons' => [
                    ['label' => '🚚 배송 시작', 'action' => "start_delivery_{$orderId}"],
                ],
            ],

            'in_delivery' => [
                'header' => '배송 중',
                'body' => "🚚 배송 중!\n주문 #{$orderId}",
                'buttons' => [
                    ['label' => '📍 지도', 'action' => "track_{$orderId}"],
                ],
            ],

            default => [
                'header' => '주문 상태',
                'body' => "주문 #{$orderId}: {$eventType}",
                'buttons' => [
                    ['label' => '📍 추적', 'action' => "track_{$orderId}"],
                ],
            ],
        };
    }

    /**
     * Send interactive message with buttons
     */
    private function sendInteractiveMessage(string $kakaoId, string $header, string $body, array $buttons): bool
    {
        $response = Http::post("{$this->apiUrl}/v2/send", [
            'sender_number' => $this->senderNumber,
            'receiver_number' => $kakaoId,
            'message' => $header . "\n\n" . $body,
            'type' => 'AT',
            'buttons' => array_map(fn($btn) => [
                'label' => $btn['label'],
                'action' => $btn['action'],
            ], $buttons),
        ], [
            'headers' => [
                'Authorization' => "KakaoAK {$this->apiKey}",
                'X-Kakao-Api-Secret' => $this->apiSecret,
                'Content-Type' => 'application/json',
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
        $links = UserKakaoTalkLink::whereIn('user_id', [$buyerId, $sellerId])
            ->active()
            ->verified()
            ->get();

        foreach ($links as $link) {
            $isBuyer = $link->user_id === $buyerId;
            $payload = $this->buildInteractivePayload($orderId, $eventType, $isBuyer, $data);

            try {
                $this->sendInteractiveMessage(
                    $link->kakao_id,
                    $payload['header'] ?? '',
                    $payload['body'],
                    $payload['buttons']
                );
                $link->markAsNotified();
            } catch (\Throwable $e) {
                Log::error('KakaoTalk interactive message failed, falling back to text', [
                    'kakao_id' => $link->kakao_id,
                    'order_id' => $orderId,
                    'event' => $eventType,
                    'error' => $e->getMessage(),
                ]);
                $message = $this->buildOrderMessage($orderId, $eventType, $isBuyer ? 'buyer' : 'seller', $data);
                $this->sendMessage($link->kakao_id, $message);
                $link->markAsNotified();
            }
        }
    }

    private function buildOrderMessage(
        int $orderId,
        string $eventType,
        string $recipientType,
        array $data
    ): string {
        $trackingUrl = url("/orders/{$orderId}");

        if ($eventType === 'created') {
            if ($recipientType === 'buyer') {
                return "[CatVRF] 주문 완료\n\n" .
                       "주문번호: {$orderId}\n" .
                       "금액: " . ($data['amount'] ?? '0') . " ₽\n\n" .
                       "주문 추적: {$trackingUrl}";
            }
            return "[CatVRF] 새 주문\n\n" .
                   "주문번호: {$orderId}\n" .
                   "금액: " . ($data['amount'] ?? '0') . " ₽";
        }

        return match($eventType) {
            'confirmed' => "[CatVRF] 주문 확인 완료\n주문번호: {$orderId}\n상품이 예약되었습니다.",
            'ready_for_delivery' => "[CatVRF] 배송 준비 완료\n주문번호: {$orderId}\n관리자에서 확인해주세요.",
            'in_delivery' => "[CatVRF] 배송 시작\n주문번호: {$orderId}\n예상 시간: " . ($data['eta'] ?? '30') . "분",
            'delivered' => "[CatVRF] 배송 완료\n주문번호: {$orderId}\n구매를 평가해주세요!",
            'cancelled' => "[CatVRF] 주문 취소\n주문번호: {$orderId}\n사유: " . ($data['reason'] ?? '미표시'),
            default => "[CatVRF] 주문 업데이트\n주문번호: {$orderId}\n상태: {$eventType}"
        };
    }
}
