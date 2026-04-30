<?php

declare(strict_types=1);

namespace Modules\Restaurant\Infrastructure\Drivers;

use Illuminate\Support\Facades\Log;
use Modules\Restaurant\Domain\Entities\OrderKitchenStatus;

final class BrowserPrintDriver extends AbstractKitchenDriver
{
    public function __construct(string $tenantId)
    {
        parent::__construct($tenantId);
    }

    public function getId(): string
    {
        return 'browser_print';
    }

    public function getName(): string
    {
        return 'Browser Print';
    }

    public function isAvailable(): bool
    {
        return $this->isEnabled();
    }

    public function getDeviceStatus(): array
    {
        $status = parent::getDeviceStatus();
        $status['type'] = 'browser_native';
        $status['requires_user_action'] = true;
        return $status;
    }

    protected function doCheckConnection(): bool
    {
        // Browser print всегда доступен через JavaScript
        return $this->isAvailable();
    }

    protected function doSendOrder(OrderKitchenStatus $orderStatus): bool
    {
        // Browser print не может инициировать печать с сервера
        // Мы сохраняем данные для печати в кэш, Livewire компонент инициирует печать
        $cacheKey = $this->getPrintCacheKey($orderStatus->orderId);
        
        $printData = [
            'order_id' => $orderStatus->orderId,
            'kitchen_station_id' => $orderStatus->kitchenStationId,
            'content' => $this->generatePrintContent($orderStatus),
            'created_at' => now()->toIso8601String(),
        ];

        \Cache::put($cacheKey, $printData, now()->addMinutes(30));

        Log::info("Print data cached for browser print", [
            'order_id' => $orderStatus->orderId,
            'cache_key' => $cacheKey,
        ]);

        return true;
    }

    protected function doUpdateOrderStatus(OrderKitchenStatus $orderStatus): bool
    {
        if ($orderStatus->status->value === 'ready') {
            $cacheKey = $this->getPrintCacheKey($orderStatus->orderId, 'ready');
            
            $printData = [
                'order_id' => $orderStatus->orderId,
                'content' => $this->generateReadySticker($orderStatus),
                'type' => 'ready_sticker',
                'created_at' => now()->toIso8601String(),
            ];

            \Cache::put($cacheKey, $printData, now()->addMinutes(30));
        }

        return true;
    }

    protected function doCancelOrder(OrderKitchenStatus $orderStatus): bool
    {
        $cacheKey = $this->getPrintCacheKey($orderStatus->orderId, 'cancel');
        
        $printData = [
            'order_id' => $orderStatus->orderId,
            'content' => $this->generateCancelSticker($orderStatus),
            'type' => 'cancel_sticker',
            'created_at' => now()->toIso8601String(),
        ];

        \Cache::put($cacheKey, $printData, now()->addMinutes(30));

        return true;
    }

    public function getPrintData(int $orderId, ?string $type = null): ?array
    {
        $cacheKey = $this->getPrintCacheKey($orderId, $type);
        $data = \Cache::get($cacheKey);
        
        if ($data !== null) {
            \Cache::forget($cacheKey);
        }

        return $data;
    }

    private function getPrintCacheKey(int $orderId, ?string $type = null): string
    {
        $suffix = $type ? ":{$type}" : '';
        return "kds:print:{$orderId}{$suffix}";
    }

    private function generatePrintContent(OrderKitchenStatus $orderStatus): string
    {
        $lines = [];
        $lines[] = "<div style='font-family: monospace; font-size: 12px;'>";
        $lines[] = "<div style='border-bottom: 2px solid #000; padding-bottom: 5px; margin-bottom: 10px;'>";
        $lines[] = "<strong>ЗАКАЗ #" . $orderStatus->orderId . "</strong>";
        $lines[] = "</div>";

        if ($orderStatus->isVip) {
            $lines[] = "<div style='color: red; font-weight: bold; margin-bottom: 5px;'>*** VIP Гость ***</div>";
        }

        if ($orderStatus->isFromMarketplace) {
            $lines[] = "<div style='color: blue; margin-bottom: 5px;'>[MARKETPLACE]</div>";
        }

        $lines[] = "<div style='margin-bottom: 5px;'>";
        $lines[] = "Приоритет: <strong>" . strtoupper($orderStatus->priority->label()) . "</strong><br>";
        $lines[] = "Время: " . $orderStatus->estimatedPreparationTime->minutes . " мин";
        $lines[] = "</div>";

        $lines[] = "<div style='border-top: 1px dashed #000; padding-top: 5px; margin: 10px 0;'>";
        $lines[] = "<em>Позиции заказа...</em>";
        $lines[] = "</div>";

        $lines[] = "<div style='border-top: 2px solid #000; padding-top: 5px; text-align: center;'>";
        $lines[] = date('H:i:s');
        $lines[] = "</div>";
        $lines[] = "</div>";

        return implode('', $lines);
    }

    private function generateReadySticker(OrderKitchenStatus $orderStatus): string
    {
        return "<div style='font-family: monospace; font-size: 14px; text-align: center; padding: 20px; border: 3px solid green;'>
            <div style='font-weight: bold; font-size: 20px; color: green;'>ГОТОВО</div>
            <div>Заказ #{$orderStatus->orderId}</div>
            <div>" . date('H:i:s') . "</div>
        </div>";
    }

    private function generateCancelSticker(OrderKitchenStatus $orderStatus): string
    {
        return "<div style='font-family: monospace; font-size: 14px; text-align: center; padding: 20px; border: 3px solid red;'>
            <div style='font-weight: bold; font-size: 20px; color: red;'>ОТМЕНО</div>
            <div>Заказ #{$orderStatus->orderId}</div>
            <div>" . date('H:i:s') . "</div>
        </div>";
    }
}
