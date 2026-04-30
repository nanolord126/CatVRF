<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class WhatsAppWebhookController extends Controller
{
    /**
     * Handle WhatsApp webhook (GET verification, POST messages)
     */
    public function webhook(Request $request): JsonResponse
    {
        // Webhook verification (GET request from WhatsApp)
        if ($request->isMethod('GET')) {
            $mode = $request->query('hub_mode');
            $token = $request->query('hub_verify_token');
            $challenge = $request->query('hub_challenge');

            if ($mode === 'subscribe' && $token === config('services.whatsapp.webhook_verify_token')) {
                return response()->json((int) $challenge);
            }

            return response()->json(['error' => 'Invalid verification token'], 403);
        }

        // Handle incoming messages (POST request)
        $payload = $request->json()->all();

        Log::info('WhatsApp webhook received', ['payload' => $payload]);

        // Process button replies
        if (isset($payload['entry'][0]['changes'][0]['value']['messages'])) {
            $messages = $payload['entry'][0]['changes'][0]['value']['messages'];

            foreach ($messages as $message) {
                $this->processMessage($message);
            }
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Process individual message
     */
    private function processMessage(array $message): void
    {
        $phone = $message['from'] ?? null;

        if (!$phone) {
            Log::warning('WhatsApp message without phone number', ['message' => $message]);
            return;
        }

        // Handle button replies
        if (isset($message['interactive']['button_reply']['id'])) {
            $buttonId = $message['interactive']['button_reply']['id'];
            $this->handleButtonReply($phone, $buttonId);
        }

        // Handle list replies
        if (isset($message['interactive']['list_reply']['id'])) {
            $listId = $message['interactive']['list_reply']['id'];
            $this->handleListReply($phone, $listId);
        }

        // Handle text messages
        if (isset($message['text']['body'])) {
            $text = $message['text']['body'];
            $this->handleTextMessage($phone, $text);
        }
    }

    /**
     * Handle button reply from interactive message
     */
    private function handleButtonReply(string $phone, string $buttonId): void
    {
        Log::info('WhatsApp button reply', ['phone' => $phone, 'button_id' => $buttonId]);

        match(true) {
            str_starts_with($buttonId, 'track_') => $this->handleTrackOrder($phone, $buttonId),
            str_starts_with($buttonId, 'accept_') => $this->handleAcceptOrder($phone, $buttonId),
            str_starts_with($buttonId, 'reject_') => $this->handleRejectOrder($phone, $buttonId),
            str_starts_with($buttonId, 'start_delivery_') => $this->handleStartDelivery($phone, $buttonId),
            str_starts_with($buttonId, 'my_orders_') => $this->handleMyOrders($phone, $buttonId),
            default => Log::warning('Unknown button ID', ['button_id' => $buttonId]),
        };
    }

    /**
     * Handle list reply from interactive list message
     */
    private function handleListReply(string $phone, string $listId): void
    {
        Log::info('WhatsApp list reply', ['phone' => $phone, 'list_id' => $listId]);

        // Handle delivery slot selection
        if (str_starts_with($listId, 'slot_')) {
            $orderId = str_replace('slot_', '', $listId);
            $this->handleDeliverySlotSelection($phone, $orderId, $listId);
        }
    }

    /**
     * Handle text message (fallback)
     */
    private function handleTextMessage(string $phone, string $text): void
    {
        Log::info('WhatsApp text message', ['phone' => $phone, 'text' => $text]);

        // Handle commands
        $command = strtolower(trim($text));

        match($command) {
            '/start', 'start' => $this->handleStart($phone),
            '/help', 'help' => $this->handleHelp($phone),
            '/orders', 'orders' => $this->handleMyOrders($phone),
            default => null,
        };
    }

    /**
     * Handle track order button
     */
    private function handleTrackOrder(string $phone, string $buttonId): void
    {
        $orderId = str_replace('track_', '', $buttonId);
        $trackingUrl = "https://catvrf.ru/orders/{$orderId}";

        // Send tracking link
        $message = "📍 Отслеживание заказа №{$orderId}\n\n{$trackingUrl}";
        app(\App\Domains\Shared\Notifications\Services\WhatsAppService::class)->sendTextMessage($phone, $message);

        Log::info('Track order button clicked', ['phone' => $phone, 'order_id' => $orderId]);
    }

    /**
     * Handle accept order button (seller)
     */
    private function handleAcceptOrder(string $phone, string $buttonId): void
    {
        $orderId = str_replace('accept_', '', $buttonId);

        // Update order status to confirmed
        // This would call SupermarketService to confirm the order
        Log::info('Accept order button clicked', ['phone' => $phone, 'order_id' => $orderId]);

        // Send confirmation message
        $message = "✅ Заказ №{$orderId} принят!\n\nТовары зарезервированы.";
        app(\App\Domains\Shared\Notifications\Services\WhatsAppService::class)->sendTextMessage($phone, $message);
    }

    /**
     * Handle reject order button (seller)
     */
    private function handleRejectOrder(string $phone, string $buttonId): void
    {
        $orderId = str_replace('reject_', '', $buttonId);

        // Update order status to cancelled
        // This would call SupermarketService to cancel the order
        Log::info('Reject order button clicked', ['phone' => $phone, 'order_id' => $orderId]);

        // Send rejection message
        $message = "❌ Заказ №{$orderId} отклонён.\n\nПокупатель будет уведомлён.";
        app(\App\Domains\Shared\Notifications\Services\WhatsAppService::class)->sendTextMessage($phone, $message);
    }

    /**
     * Handle start delivery button
     */
    private function handleStartDelivery(string $phone, string $buttonId): void
    {
        $orderId = str_replace('start_delivery_', '', $buttonId);

        // Update order status to in_delivery
        // This would call SupermarketService to start delivery
        Log::info('Start delivery button clicked', ['phone' => $phone, 'order_id' => $orderId]);

        // Send confirmation message
        $message = "🚚 Курьер отправлен!\n\nЗаказ №{$orderId} в пути.";
        app(\App\Domains\Shared\Notifications\Services\WhatsAppService::class)->sendTextMessage($phone, $message);
    }

    /**
     * Handle my orders button
     */
    private function handleMyOrders(string $phone, string $buttonId = ''): void
    {
        $ordersUrl = "https://catvrf.ru/orders";

        // Send orders link
        $message = "🛍 Мои заказы\n\n{$ordersUrl}";
        app(\App\Domains\Shared\Notifications\Services\WhatsAppService::class)->sendTextMessage($phone, $message);

        Log::info('My orders button clicked', ['phone' => $phone]);
    }

    /**
     * Handle delivery slot selection
     */
    private function handleDeliverySlotSelection(string $phone, string $orderId, string $slotId): void
    {
        // Update order with selected delivery slot
        Log::info('Delivery slot selected', ['phone' => $phone, 'order_id' => $orderId, 'slot_id' => $slotId]);

        $message = "✅ Слот доставки выбран!\n\nЗаказ №{$orderId}";
        app(\App\Domains\Shared\Notifications\Services\WhatsAppService::class)->sendTextMessage($phone, $message);
    }

    /**
     * Handle start command
     */
    private function handleStart(string $phone): void
    {
        $message = "👋 Добро пожаловать в CatVRF!\n\n" .
                   "Доступные команды:\n" .
                   "📦 /orders - Мои заказы\n" .
                   "❓ /help - Справка";

        app(\App\Domains\Shared\Notifications\Services\WhatsAppService::class)->sendTextMessage($phone, $message);
    }

    /**
     * Handle help command
     */
    private function handleHelp(string $phone): void
    {
        $message = "❓ Справка\n\n" .
                   "📍 Отслеживание заказа: нажмите кнопку \"Отследить\" в сообщении о заказе\n" .
                   "✅ Принятие заказа: продавец может принять или отклонить заказ\n" .
                   "🚚 Доставка: нажмите кнопку для передачи заказа курьеру\n\n" .
                   "Для связи с поддержкой: support@catvrf.ru";

        app(\App\Domains\Shared\Notifications\Services\WhatsAppService::class)->sendTextMessage($phone, $message);
    }
}
