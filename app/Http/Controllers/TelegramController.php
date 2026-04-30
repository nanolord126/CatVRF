<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domains\Shared\Notifications\Services\TelegramBotService;
use App\Models\User;
use App\Models\UserTelegramLink;
use App\Domains\Supermarket\Models\SupermarketOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final readonly class TelegramController
{
    public function __construct(
        private TelegramBotService $telegramService
    ) {}

    /**
     * Handle Telegram webhook updates
     */
    public function webhook(Request $request): JsonResponse
    {
        $update = $request->json()->all();

        Log::info('Telegram webhook received', ['update' => $update]);

        // Handle callback_query (inline keyboard buttons)
        if (isset($update['callback_query'])) {
            $this->handleCallbackQuery($update['callback_query']);
            return response()->json(['ok' => true]);
        }

        if (!isset($update['message'])) {
            return response()->json(['ok' => true]);
        }

        $message = $update['message'];
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? null;

        if (!$text) {
            return response()->json(['ok' => true]);
        }

        // Handle /start command with verification token
        if (str_starts_with($text, '/start')) {
            $token = str_replace('/start ', '', $text);
            
            if ($token !== '/start') {
                $this->handleLinkAccount($chatId, $token);
            } else {
                $this->sendWelcomeMessage($chatId);
            }
        }
        // Handle /help command
        elseif ($text === '/help') {
            $this->sendHelpMessage($chatId);
        }
        // Handle /unsubscribe command
        elseif ($text === '/unsubscribe') {
            $this->handleUnsubscribe($chatId);
        }
        // Handle /status command
        elseif ($text === '/status') {
            $this->sendStatusMessage($chatId);
        }callback_query from inline keyboard buttons
     */
    private function handleCallbackQuery(array $callbackQuery): void
    {
        $data = $callbackQuery['data'];
        $chatId = $callbackQuery['message']['chat']['id'];
        $callbackQueryId = $callbackQuery['id'];

        // Acknowledge the callback
        $this->answerCallbackQuery($callbackQueryId);

        match(true) {
            str_starts_with($data, 'track_order_') => $this->handleTrackOrder($chatId, $data),
            str_starts_with($data, 'accept_order_') => $this->handleAcceptOrder($chatId, $data),
            str_starts_with($data, 'reject_order_') => $this->handleRejectOrder($chatId, $data),
            str_starts_with($data, 'start_delivery_') => $this->handleStartDelivery($chatId, $data),
            str_starts_with($data, 'report_issue_') => $this->handleReportIssue($chatId, $data),
            str_starts_with($data, 'show_map_') => $this->handleShowMap($chatId, $data),
            str_starts_with($data, 'rate_order_') => $this->handleRateOrder($chatId, $data),
            str_starts_with($data, 'view_receipt_') => $this->handleViewReceipt($chatId, $data),
            $data === 'my_orders' => $this->handleMyOrders($chatId),
            default => Log::warning('Unknown callback data', ['data' => $data])
        };
    }

    /**
     * Answer callback query to stop loading animation
     */
    private function answerCallbackQuery(string $callbackQueryId, string $text = null): void
    {
        try {
            $payload = ['callback_query_id' => $callbackQueryId];
            if ($text) {
                $payload['text'] = $text;
            }

            Http::post(
                'https://api.telegram.org/bot' . $this->telegramService->getBotToken() . '/answerCallbackQuery',
                $payload
            );
        } catch (\Throwable $e) {
            Log::error('Failed to answer callback query', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Handle 

        return response()->json(['ok' => true]);
    }

    /**
     * Handle account linking via verification token
     */
    private function handleLinkAccount(int $chatId, string $token): void
    {
        $link = $this->telegramService->linkAccount($chatId, $token);

        if ($link) {
            $user = $link->user;
            
            $message = "<b>✅ Аккаунт успешно привязан!</b>\n\n";
            $message .= "Пользователь: {$user->name}\n";
            $message .= "Email: {$user->email}\n\n";
            $message .= "Теперь вы будете получать уведомления о заказах в Telegram.";

            $this->telegramService->sendMessage($chatId, $message);

            Log::info('Telegram account linked', [
                'user_id' => $link->user_id,
                'telegram_id' => $chatId,
            ]);
        } else {
            $message = "<b>❌ Неверный токен или ссылка истекла</b>\n\n";
            $message .= "Пожалуйста, получите новую ссылку для привязки аккаунта в настройках профиля.";

            $this->telegramService->sendMessage($chatId, $message);

            Log::warning('Failed to link Telegram account - invalid token', [
                'telegram_id' => $chatId,
                'token' => $token,
            ]);
        }
    }

    /**
     * Send welcome message
     */
    private function sendWelcomeMessage(int $chatId): void
    {
        $message = "<b>👋 Добро пожаловать в CatVRF Bot!</b>\n\n";
        $message .= "Этот бот отправляет уведомления о ваших заказах.\n\n";
        $message .= "<b>Доступные команды:</b>\n";
        $message .= "/start &lt;token&gt; - Привязать аккаунт\n";
        $message .= "/help - Справка\n";
        $message .= "/status - Статус привязки\n";
        $message .= "/unsubscribe - Отписаться от уведомлений\n\n";
        $message .= "Для привязки аккаунта перейдите по ссылке из настроек профиля.";

        $this->telegramService->sendMessage($chatId, $message);
    }

    /**
     * Send help message
     */
    private function sendHelpMessage(int $chatId): void
    {
        $message = "<b>📚 Справка</b>\n\n";
        $message .= "<b>Команды:</b>\n";
        $message .= "/start &lt;token&gt; - Привязать аккаунт CatVRF к Telegram\n";
        $message .= "/help - Показать эту справку\n";
        $message .= "/status - Проверить статус привязки\n";
        $message .= "/unsubscribe - Отписаться от уведомлений\n\n";
        $message .= "<b>Как получить токен?</b>\n";
        $message .= "1. Зайдите в настройки профиля на сайте\n";
        $message .= "2. Нажмите 'Привязать Telegram'\n";
        $message .= "3. Скопируйте ссылку или перейдите по ней\n\n";
        $message .= "<b>Какие уведомления приходят?</b>\n";
        $message .= "✅ Создание заказа\n";
        $message .= "✅ Подтверждение заказа\n";
        $message .= "📦 Заказ готов к доставке\n";
        $message .= "🚚 Курьер в пути\n";
        $message .= "✅ Заказ доставлен\n";
        $message .= "❌ Отмена заказа";

        $this->telegramService->sendMessage($chatId, $message);
    }

    /**
     * Handle unsubscribe
     */
    private function handleUnsubscribe(int $chatId): void
    {
        $link = UserTelegramLink::where('telegram_id', $chatId)->first();

        if ($link) {
            $this->telegramService->unlinkAccount($link->user_id);
            
            $message = "<b>✅ Вы отписались от уведомлений</b>\n\n";
            $message .= "Для повторной подписки используйте команду /start с новым токеном.";

            $this->telegramService->sendMessage($chatId, $message);

            Log::info('Telegram account unlinked', [
                'user_id' => $link->user_id,
                'telegram_id' => $chatId,
            ]);
        } else {
            $message = "<b>❌ Аккаунт не привязан</b>\n\n";
            $message .= "У вас нет привязанного аккаунта CatVRF.";

            $this->telegramService->sendMessage($chatId, $message);
        }
    }

    /**
     * Handle track_order callback
     */
    private function handleTrackOrder(int $chatId, string $data): void
    {
        $orderId = (int) str_replace('track_order_', '', $data);
        $order = SupermarketOrder::find($orderId);

        if (!$order) {
            $this->telegramService->sendMessage($chatId, '❌ Заказ не найден');
            return;
        }

        $message = "<b>📍 Заказ №{$order->id}</b>\n\n";
        $message .= "Статус: {$order->status}\n";
        $message .= "Сумма: {$order->total_amount} ₽\n";
        $message .= "Доставка: {$order->delivery_cost} ₽\n";
        $message .= "ETA: {$order->delivery_eta} мин\n";

        $keyboard = [
            [
                ['text' => '🔄 Обновить статус', 'callback_data' => "track_order_{$orderId}"],
            ]
        ];

        $this->telegramService->sendMessage($chatId, $message, $keyboard);
    }

    /**
     * Handle accept_order callback
     */
    private function handleAcceptOrder(int $chatId, string $data): void
    {
        $orderId = (int) str_replace('accept_order_', '', $data);
        $order = SupermarketOrder::find($orderId);

        if (!$order) {
            $this->telegramService->sendMessage($chatId, '❌ Заказ не найден');
            return;
        }

        $order->update(['status' => 'confirmed']);

        // Trigger notification via hub
        app(\App\Domains\Shared\Notifications\Services\NotificationHub::class)->dispatchOrderNotification(
            vertical: 'supermarket',
            orderId: $orderId,
            buyerId: $order->user_id,
            sellerId: 1, // Default seller ID
            eventType: 'confirmed',
            data: []
        );

        $this->telegramService->sendMessage($chatId, "✅ Заказ №{$orderId} принят!\nТовары зарезервированы.");
    }

    /**
     * Handle reject_order callback
     */
    private function handleRejectOrder(int $chatId, string $data): void
    {
        $orderId = (int) str_replace('reject_order_', '', $data);
        $order = SupermarketOrder::find($orderId);

        if (!$order) {
            $this->telegramService->sendMessage($chatId, '❌ Заказ не найден');
            return;
        }

        $order->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        app(\App\Domains\Shared\Notifications\Services\NotificationHub::class)->dispatchOrderNotification(
            vertical: 'supermarket',
            orderId: $orderId,
            buyerId: $order->user_id,
            sellerId: 1,
            eventType: 'cancelled',
            data: ['reason' => 'Отклонен продавцом']
        );

        $this->telegramService->sendMessage($chatId, "❌ Заказ №{$orderId} отклонён.");
    }

    /**
     * Handle start_delivery callback
     */
    private function handleStartDelivery(int $chatId, string $data): void
    {
        $orderId = (int) str_replace('start_delivery_', '', $data);
        $order = SupermarketOrder::find($orderId);

        if (!$order) {
            $this->telegramService->sendMessage($chatId, '❌ Заказ не найден');
            return;
        }

        $order->update(['status' => 'in_delivery']);

        app(\App\Domains\Shared\Notifications\Services\NotificationHub::class)->dispatchOrderNotification(
            vertical: 'supermarket',
            orderId: $orderId,
            buyerId: $order->user_id,
            sellerId: 1,
            eventType: 'in_delivery',
            data: ['eta' => $order->delivery_eta]
        );

        $this->telegramService->sendMessage($chatId, "🚚 Заказ №{$orderId} передан в доставку!");
    }

    /**
     * Handle report_issue callback
     */
    private function handleReportIssue(int $chatId, string $data): void
    {
        $orderId = (int) str_replace('report_issue_', '', $data);

        $message = "<b>📝 Сообщить о проблеме</b>\n\n";
        $message .= "Заказ №{$orderId}\n\n";
        $message .= "Пожалуйста, опишите проблему в ответном сообщении.\n";
        $message .= "Наш менеджер свяжется с вами в ближайшее время.";

        $this->telegramService->sendMessage($chatId, $message);

        Log::info('Issue reported via Telegram', [
            'order_id' => $orderId,
            'chat_id' => $chatId,
        ]);
    }

    /**
     * Handle show_map callback
     */
    private function handleShowMap(int $chatId, string $data): void
    {
        $orderId = (int) str_replace('show_map_', '', $data);
        
        $message = "<b>📍 Карта доставки</b>\n\n";
        $message .= "Заказ №{$orderId}\n\n";
        $message .= "Для отслеживания курьера перейдите в приложение:\n";
        $message .= "https://catvrf.ru/orders/{$orderId}";

        $this->telegramService->sendMessage($chatId, $message);
    }

    /**
     * Handle rate_order callback
     */
    private function handleRateOrder(int $chatId, string $data): void
    {
        $orderId = (int) str_replace('rate_order_', '', $data);

        $message = "<b>⭐ Оценить заказ</b>\n\n";
        $message .= "Заказ №{$orderId}\n\n";
        $message .= "Пожалуйста, оцените заказ от 1 до 5 звёзд в ответном сообщении.";

        $this->telegramService->sendMessage($chatId, $message);
    }

    /**
     * Handle view_receipt callback
     */
    private function handleViewReceipt(int $chatId, string $data): void
    {
        $orderId = (int) str_replace('view_receipt_', '', $data);
        $order = SupermarketOrder::find($orderId);

        if (!$order) {
            $this->telegramService->sendMessage($chatId, '❌ Заказ не найден');
            return;
        }

        $message = "<b>📋 Чек заказа №{$orderId}</b>\n\n";
        $message .= "Сумма: {$order->total_amount} ₽\n";
        $message .= "Доставка: {$order->delivery_cost} ₽\n";
        $message .= "Итого: " . ($order->total_amount + $order->delivery_cost) . " ₽\n";
        $message .= "Дата: {$order->created_at->format('d.m.Y H:i')}\n\n";
        $message .= "Полный чек доступен в приложении.";

        $this->telegramService->sendMessage($chatId, $message);
    }

    /**
     * Handle my_orders callback
     */
    private function handleMyOrders(int $chatId): void
    {
        $link = UserTelegramLink::where('telegram_id', $chatId)->first();

        if (!$link) {
            $this->telegramService->sendMessage($chatId, '❌ Аккаунт не привязан');
            return;
        }

        $orders = SupermarketOrder::where('user_id', $link->user_id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        if ($orders->isEmpty()) {
            $this->telegramService->sendMessage($chatId, 'У вас пока нет заказов');
            return;
        }

        $message = "<b>🛍 Мои заказы</b>\n\n";

        foreach ($orders as $order) {
            $message .= "№{$order->id} - {$order->status} ({$order->total_amount} ₽)\n";
        }

        $message .= "\nПолный список в приложении.";

        $this->telegramService->sendMessage($chatId, $message);
    }

    /**
     * Send status message
     */
    private function sendStatusMessage(int $chatId): void
    {
        $link = UserTelegramLink::where('telegram_id', $chatId)->first();

        if ($link && $link->is_active) {
            $user = $link->user;
            
            $message = "<b>✅ Статус привязки: Активен</b>\n\n";
            $message .= "Пользователь: {$user->name}\n";
            $message .= "Email: {$user->email}\n";
            $message .= "Последнее уведомление: ";
            $message .= $link->last_notified_at ? $link->last_notified_at->diffForHumans() : 'Никогда';
        } else {
            $message = "<b>❌ Статус привязки: Неактивен</b>\n\n";
            $message .= "Аккаунт не привязан или отключен.\n";
            $message .= "Используйте /start &lt;token&gt; для привязки.";
        }

        $this->telegramService->sendMessage($chatId, $message);
    }

    /**
     * Generate verification link for user (API endpoint)
     */
    public function generateLink(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $userId = $request->input('user_id');
        $link = $this->telegramService->generateVerificationLink($userId);

        return response()->json([
            'success' => true,
            'link' => $link,
        ]);
    }

    /**
     * Set webhook (for setup)
     */
    public function setWebhook(Request $request): JsonResponse
    {
        $request->validate([
            'url' => 'required|url',
        ]);

        $success = $this->telegramService->setWebhook($request->input('url'));

        return response()->json([
            'success' => $success,
        ]);
    }

    /**
     * Get webhook info
     */
    public function getWebhookInfo(): JsonResponse
    {
        $info = $this->telegramService->getWebhookInfo();

        return response()->json([
            'info' => $info,
        ]);
    }

    /**
     * Delete webhook
     */
    public function deleteWebhook(): JsonResponse
    {
        $success = $this->telegramService->deleteWebhook();

        return response()->json([
            'success' => $success,
        ]);
    }
}
