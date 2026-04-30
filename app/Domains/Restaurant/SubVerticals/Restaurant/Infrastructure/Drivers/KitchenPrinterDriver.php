<?php

declare(strict_types=1);

namespace Modules\Restaurant\Infrastructure\Drivers;

use Illuminate\Support\Facades\Log;
use Modules\Restaurant\Domain\Entities\OrderKitchenStatus;
use Modules\Restaurant\Domain\Enums\PrinterModel;
use Modules\Restaurant\Infrastructure\Printers\PrinterCommandSet;

final class KitchenPrinterDriver extends AbstractKitchenDriver
{
    public function __construct(string $tenantId)
    {
        parent::__construct($tenantId);
    }

    public function getId(): string
    {
        return 'kitchen_printer';
    }

    public function getName(): string
    {
        return 'Kitchen Printer (Star Micronics/Epson/Citizen)';
    }

    public function isAvailable(): bool
    {
        return $this->isEnabled() && !empty($this->getConfig('printers', []));
    }

    protected function doCheckConnection(): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        $printers = $this->getConfig('printers', []);
        $connected = 0;

        foreach ($printers as $stationId => $printer) {
            if ($this->checkPrinterConnection($printer)) {
                $connected++;
            }
        }

        return $connected > 0;
    }

    public function getDeviceStatus(): array
    {
        $status = parent::getDeviceStatus();
        $printers = $this->getConfig('printers', []);
        $status['total'] = count($printers);
        $status['online'] = 0;
        $status['offline'] = 0;
        $status['printers'] = [];

        foreach ($printers as $stationId => $printer) {
            $isOnline = $this->checkPrinterConnection($printer);
            
            $printerInfo = [
                'station_id' => $stationId,
                'name' => $printer['name'] ?? 'Unknown',
                'type' => $printer['type'] ?? 'unknown',
                'status' => $isOnline ? 'online' : 'offline',
            ];

            if ($isOnline) {
                $status['online']++;
            } else {
                $status['offline']++;
            }

            // Добавляем информацию о модели если указана
            if (isset($printer['model']) && is_string($printer['model'])) {
                try {
                    $model = PrinterModel::from($printer['model']);
                    $printerInfo['model'] = $model->value;
                    $printerInfo['brand'] = $model->brand();
                    $printerInfo['model_name'] = $model->modelName();
                    $printerInfo['command_set'] = $model->commandSet();
                    $printerInfo['paper_width'] = $model->paperWidth();
                } catch (\ValueError $e) {
                    $printerInfo['model'] = $printer['model'];
                    $printerInfo['model_error'] = 'Invalid model';
                }
            }

            $status['printers'][] = $printerInfo;
        }

        return $status;
    }

    protected function doSendOrder(OrderKitchenStatus $orderStatus): bool
    {
        $printers = $this->getConfig('printers', []);
        $printer = $printers[$orderStatus->kitchenStationId] ?? null;

        if ($printer === null) {
            Log::warning("Printer not found for station", [
                'station_id' => $orderStatus->kitchenStationId,
            ]);
            return false;
        // Получаем модель принтера из конфигурации
        $model = null;
        if (isset($printer['model']) && is_string($printer['model'])) {
            try {
                $model = PrinterModel::from($printer['model']);
            } catch (\ValueError $e) {
                Log::warning("Invalid printer model", [
                    'model' => $printer['model'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $this->printOrder($orderStatus, $printer);
    }

    protected function doUpdateOrderStatus(OrderKitchenStatus $orderStatus): bool
    {
        // Для принтеров обновление статуса обычно не требуется
        // Но можно печатать стикер "ГОТОВО"
        if ($orderStatus->status->value === 'ready') {
            return $this->printReadySticker($orderStatus);
        }

        return true;
    }

    protected function doCancelOrder(OrderKitchenStatus $orderStatus): bool
    {
        // Печать стикера "ОТМЕНО"
        return $this->printCancelSticker($orderStatus);
    }

    private function checkPrinterConnection(array $printer): bool
    {
        $type = $printer['type'] ?? 'network';
        
        return match ($type) {
            'network' => $this->checkNetworkPrinter($printer),
            'usb' => $this->checkUsbPrinter($printer),
            'bluetooth' => $this->checkBluetoothPrinter($printer),
            default => false,
        };
    }

    private function checkNetworkPrinter(array $printer): bool
    {
        $host = $printer['host'] ?? null;
        $port = $printer['port'] ?? 9100;

        if ($host === null) {
            return false;
        }

        $connection = @fsockopen($host, $port, $errno, $errstr, 2);

        if ($connection) {
            fclose($connection);
            return true;
        }

        return false;
    }

    private function checkUsbPrinter(array $printer): bool
    {
        // Для USB принтеров используем lpstat на Linux или проверку через driver
        $device = $printer['device'] ?? null;
        
        if ($device === null) {
            return false;
        }

        return file_exists($device);
    }

    private function checkBluetoothPrinter(array $printer): bool
    {
        // Bluetooth принтеры проверяются через bluez или специфический driver
        return true; // Placeholder - требует специфической реализации
    }

    private function printOrder(OrderKitchenStatus $orderStatus, array $printer): bool
    {
        $content = $this->generateOrderTicket($orderStatus);
        
        return match ($printer['type'] ?? 'network') {
            'network' => $this->printToNetworkPrinter($printer, $content),
            'usb' => $this->printToUsbPrinter($printer, $content),
            'bluetooth' => $this->printToBluetoothPrinter($printer, $content),
            default => false,
        };
    }

    private function generateOrderTicket(OrderKitchenStatus $orderStatus): string
    {
        $lines = [];
        $lines[] = str_repeat('=', 40);
        $lines[] = 'ЗАКАЗ #' . $orderStatus->orderId;
        $lines[] = str_repeat('=', 40);
        $lines[] = '';

        if ($orderStatus->isVip) {
            $lines[] = '*** VIP Гость ***';
            $lines[] = '';
        }

        if ($orderStatus->isFromMarketplace) {
            $lines[] = '[MARKETPLACE]';
            $lines[] = '';
        }

        $lines[] = 'Приоритет: ' . strtoupper($orderStatus->priority->label());
        $lines[] = 'Время: ' . $orderStatus->estimatedPreparationTime->minutes . ' мин';
        $lines[] = '';
        $lines[] = str_repeat('-', 40);
        $lines[] = '';

        // Здесь можно добавить позиции заказа из Order
        $order = \App\Models\Order::with('items')->find($orderStatus->orderId);
        if ($order) {
            foreach ($order->items as $item) {
                $lines[] = "{$item->quantity}x {$item->product_name}";
                if (!empty($item->options)) {
                    $lines[] = '   ' . json_encode($item->options);
                }
            }
        }
        
        $lines[] = '';
        $lines[] = str_repeat('=', 40);
        $lines[] = date('H:i:s');
        $lines[] = str_repeat('=', 40);

        return implode("\n", $lines) . "\n\n\n";
    }

    private function printToNetworkPrinter(array $printer, string $content): bool
    {
        $host = $printer['host'] ?? null;
        $port = $printer['port'] ?? 9100;

        if ($host === null) {
            return false;
        }

        $fp = @fsockopen($host, $port, $errno, $errstr, 5);

        if (!$fp) {
            Log::error("Failed to connect to printer", [
                'host' => $host,
                'port' => $port,
                'error' => $errstr,
            ]);
            return false;
        }

        $result = fwrite($fp, $content);
        fclose($fp);

        return $result !== false;
    }

    private function printToUsbPrinter(array $printer, string $content): bool
    {
        $device = $printer['device'] ?? null;

        if ($device === null) {
            return false;
        }

        $fp = @fopen($device, 'w');

        if (!$fp) {
            Log::error("Failed to open USB printer", ['device' => $device]);
            return false;
        }

        $result = fwrite($fp, $content);
        fclose($fp);

        return $result !== false;
    }

    private function printToBluetoothPrinter(array $printer, string $content): bool
    {
        // Bluetooth печать через rfcomm или специфический driver
        // Placeholder - требует специфической реализации
        Log::warning("Bluetooth printing not implemented yet");
        return false;
    }

    private function printReadySticker(OrderKitchenStatus $orderStatus): bool
    {
        $content = "=== ГОТОВО ===\nЗаказ #{$orderStatus->orderId}\n" . date('H:i:s') . "\n\n";
        
        $printers = $this->getConfig('printers', []);
        $printer = $printers[$orderStatus->kitchenStationId] ?? null;

        if ($printer === null) {
            return false;
        }

        return $this->printOrder($orderStatus, $printer);
    }

    private function printCancelSticker(OrderKitchenStatus $orderStatus): bool
    {
        $content = "=== ОТМЕНО ===\nЗаказ #{$orderStatus->orderId}\n" . date('H:i:s') . "\n\n";
        
        $printers = $this->getConfig('printers', []);
        $printer = $printers[$orderStatus->kitchenStationId] ?? null;

        if ($printer === null) {
            return false;
        }

        return $this->printOrder($orderStatus, $printer);
    }
}
