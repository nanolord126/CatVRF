<?php

declare(strict_types=1);

namespace Modules\Restaurant\Infrastructure\Printers;

use Modules\Restaurant\Domain\Enums\PrinterModel;

/**
 * Генератор команд принтера для разных наборов команд (ESC/POS, StarLine, ZPL)
 */
final class PrinterCommandSet
{
    public static function generateCommands(PrinterModel $model, array $data): string
    {
        return match ($model->commandSet()) {
            'esc_pos' => self::generateEscPos($model, $data),
            'starline' => self::generateStarLine($model, $data),
            'zpl' => self::generateZpl($model, $data),
            default => throw new \InvalidArgumentException("Unknown command set: {$model->commandSet()}"),
        };
    }

    private static function generateEscPos(PrinterModel $model, array $data): string
    {
        $commands = [];

        // Initialize printer
        $commands[] = chr(0x1B) . '@'; // ESC @ - Initialize

        // Set character code table (UTF-8 support)
        $commands[] = chr(0x1B) . chr(0x74) . chr(0x11); // ESC t 17 - UTF-8

        // Set alignment
        $commands[] = chr(0x1B) . chr(0x61) . chr(0x00); // ESC a 0 - Left align

        // Set font size (double width/height)
        $commands[] = chr(0x1D) . chr(0x21) . chr(0x11); // GS ! 17 - Double size

        // Print header
        $commands[] = self::escPosText(str_repeat('=', 32), false);
        $commands[] = self::escPosText($data['title'] ?? 'ORDER', true);
        $commands[] = self::escPosText("Order #{$data['order_id']}", true);
        $commands[] = self::escPosText(str_repeat('=', 32), false);

        // Reset font size
        $commands[] = chr(0x1D) . chr(0x21) . chr(0x00); // GS ! 0 - Normal size

        // VIP badge
        if ($data['is_vip'] ?? false) {
            $commands[] = chr(0x1B) . chr(0x45) . chr(0x01); // ESC E 1 - Bold on
            $commands[] = self::escPosText('*** VIP GUEST ***', false);
            $commands[] = chr(0x1B) . chr(0x45) . chr(0x00); // ESC E 0 - Bold off
        }

        // Marketplace badge
        if ($data['is_from_marketplace'] ?? false) {
            $commands[] = self::escPosText('[MARKETPLACE]', false);
        }

        // Priority
        $commands[] = self::escPosText("Priority: " . strtoupper($data['priority'] ?? 'NORMAL'), false);
        $commands[] = self::escPosText("Time: {$data['estimated_minutes']} min", false);

        // Separator
        $commands[] = self::escPosText(str_repeat('-', 32), false);

        // Items
        foreach ($data['items'] ?? [] as $item) {
            $commands[] = self::escPosText("{$item['quantity']}x {$item['name']}", false);
            if (!empty($item['modifiers'])) {
                $commands[] = self::escPosText("  " . implode(', ', $item['modifiers']), false);
            }
        }

        // Footer
        $commands[] = self::escPosText(str_repeat('=', 32), false);
        $commands[] = self::escPosText(date('H:i:s'), false);
        $commands[] = self::escPosText(str_repeat('=', 32), false);

        // Cut paper
        $commands[] = chr(0x1D) . chr(0x56) . chr(0x01); // GS V 1 - Full cut

        // Feed and cut
        $commands[] = chr(0x0A) . chr(0x0A) . chr(0x0A); // 3x LF

        return implode('', $commands);
    }

    private static function generateStarLine(PrinterModel $model, array $data): string
    {
        $commands = [];

        // Initialize
        $commands[] = chr(0x1B) . '@'; // ESC @

        // Set code page
        $commands[] = chr(0x1B) . chr(0x74) . chr(0x00); // Code page 0

        // Center align
        $commands[] = chr(0x1B) . chr(0x61) . chr(0x01); // ESC a 1

        // Header
        $commands[] = str_repeat('=', 32) . "\n";
        $commands[] = ($data['title'] ?? 'ORDER') . "\n";
        $commands[] = "Order #{$data['order_id']}\n";
        $commands[] = str_repeat('=', 32) . "\n";

        // Left align
        $commands[] = chr(0x1B) . chr(0x61) . chr(0x00); // ESC a 0

        // VIP
        if ($data['is_vip'] ?? false) {
            $commands[] = chr(0x1B) . chr(0x45) . chr(0x01); // Bold on
            $commands[] = "*** VIP GUEST ***\n";
            $commands[] = chr(0x1B) . chr(0x45) . chr(0x00); // Bold off
        }

        // Marketplace
        if ($data['is_from_marketplace'] ?? false) {
            $commands[] = "[MARKETPLACE]\n";
        }

        // Priority
        $commands[] = "Priority: " . strtoupper($data['priority'] ?? 'NORMAL') . "\n";
        $commands[] = "Time: {$data['estimated_minutes']} min\n";

        // Separator
        $commands[] = str_repeat('-', 32) . "\n";

        // Items
        foreach ($data['items'] ?? [] as $item) {
            $commands[] = "{$item['quantity']}x {$item['name']}\n";
            if (!empty($item['modifiers'])) {
                $commands[] = "  " . implode(', ', $item['modifiers']) . "\n";
            }
        }

        // Footer
        $commands[] = str_repeat('=', 32) . "\n";
        $commands[] = date('H:i:s') . "\n";
        $commands[] = str_repeat('=', 32) . "\n";

        // Cut
        $commands[] = chr(0x1D) . chr(0x56) . chr(0x00); // GS V 0 - Partial cut

        // Feed
        $commands[] = "\n\n\n";

        return implode('', $commands);
    }

    private static function generateZpl(PrinterModel $model, array $data): string
    {
        $commands = [];

        // Start label
        $commands[] = "^XA";

        // Set label width
        $commands[] = "^PW600"; // 600 dots (~80mm at 8dpmm)

        // Set label home
        $commands[] = "^LH0,0";

        // Font
        $commands[] = "^A0N,40,40"; // Font 0, normal, 40 dots

        // Header
        $commands[] = "^FO50,50^FD" . str_repeat('=', 30) . "^FS";
        $commands[] = "^FO50,100^FD" . ($data['title'] ?? 'ORDER') . "^FS";
        $commands[] = "^FO50,150^FDOrder #{$data['order_id']}^FS";
        $commands[] = "^FO50,200^FD" . str_repeat('=', 30) . "^FS";

        // VIP
        if ($data['is_vip'] ?? false) {
            $commands[] = "^FO50,250^FD*** VIP GUEST ***^FS";
        }

        // Marketplace
        if ($data['is_from_marketplace'] ?? false) {
            $commands[] = "^FO50,300^FD[MARKETPLACE]^FS";
        }

        // Priority
        $commands[] = "^FO50,350^FDPriority: " . strtoupper($data['priority'] ?? 'NORMAL') . "^FS";
        $commands[] = "^FO50,400^FDTime: {$data['estimated_minutes']} min^FS";

        // Separator
        $commands[] = "^FO50,450^FD" . str_repeat('-', 30) . "^FS";

        // Items
        $y = 500;
        foreach ($data['items'] ?? [] as $item) {
            $commands[] = "^FO50,{$y}^FD{$item['quantity']}x {$item['name']}^FS";
            $y += 50;
            if (!empty($item['modifiers'])) {
                $commands[] = "^FO70,{$y}^FD" . implode(', ', $item['modifiers']) . "^FS";
                $y += 50;
            }
        }

        // Footer
        $commands[] = "^FO50,{$y}^FD" . str_repeat('=', 30) . "^FS";
        $commands[] = "^FO50,{$y + 50}^FD" . date('H:i:s') . "^FS";
        $commands[] = "^FO50,{$y + 100}^FD" . str_repeat('=', 30) . "^FS";

        // End label
        $commands[] = "^XZ";

        return implode('', $commands);
    }

    private static function escPosText(string $text, bool $bold): string
    {
        $command = '';
        
        if ($bold) {
            $command .= chr(0x1B) . chr(0x45) . chr(0x01); // ESC E 1 - Bold on
        }

        $command .= $text;

        if ($bold) {
            $command .= chr(0x1B) . chr(0x45) . chr(0x00); // ESC E 0 - Bold off
        }

        $command .= "\n";

        return $command;
    }

    public static function generateBarcode(PrinterModel $model, string $data, string $type = 'CODE128'): string
    {
        if (!$model->supportsBarcode()) {
            return '';
        }

        return match ($model->commandSet()) {
            'esc_pos' => self::escPosBarcode($data, $type),
            'starline' => self::starLineBarcode($data, $type),
            'zpl' => self::zplBarcode($data, $type),
            default => '',
        };
    }

    private static function escPosBarcode(string $data, string $type): string
    {
        $commands = [];

        // Set barcode height
        $commands[] = chr(0x1D) . chr(0x68) . chr(100); // GS h 100

        // Set barcode width
        $commands[] = chr(0x1D) . chr(0x77) . chr(2); // GS w 2

        // Print barcode
        $commands[] = chr(0x1D) . chr(0x6B) . chr(0x04) . $data . chr(0x00); // GS k 4 - CODE128

        // Feed
        $commands[] = "\n";

        return implode('', $commands);
    }

    private static function starLineBarcode(string $data, string $type): string
    {
        // StarLine barcode commands
        $commands = [];

        $commands[] = chr(0x1D) . chr(0x68) . chr(100); // Set height
        $commands[] = chr(0x1D) . chr(0x77) . chr(2); // Set width
        $commands[] = chr(0x1D) . chr(0x6B) . chr(0x04) . $data . chr(0x00); // CODE128

        return implode('', $commands);
    }

    private static function zplBarcode(string $data, string $type): string
    {
        $commands = [];

        // Barcode position
        $commands[] = "^FO50,800";

        // Barcode type and data
        $commands[] = "^BCN,100,Y,N,N"; // Code 128, 100 dots, no interpretation
        $commands[] = "^FD" . $data . "^FS";

        return implode('', $commands);
    }

    public static function generateQR(PrinterModel $model, string $data): string
    {
        if (!$model->supportsQR()) {
            return '';
        }

        return match ($model->commandSet()) {
            'esc_pos' => self::escPosQR($data),
            'starline' => self::starLineQR($data),
            'zpl' => self::zplQR($data),
            default => '',
        };
    }

    private static function escPosQR(string $data): string
    {
        $commands = [];

        // QR code model
        $commands[] = chr(0x1D) . chr(0x28) . chr(0x6B) . chr(0x04) . chr(0x00) . chr(0x31) . chr(0x41) . chr(0x32) . chr(0x00); // QR model 2

        // QR code size
        $commands[] = chr(0x1D) . chr(0x28) . chr(0x6B) . chr(0x03) . chr(0x00) . chr(0x31) . chr(0x43) . chr(0x08); // Size 8

        // QR code error correction
        $commands[] = chr(0x1D) . chr(0x28) . chr(0x6B) . chr(0x03) . chr(0x00) . chr(0x31) . chr(0x45) . chr(0x51); // Level L

        // QR code data
        $length = strlen($data);
        $commands[] = chr(0x1D) . chr(0x28) . chr(0x6B) . chr($length % 256) . chr($length / 256) . chr(0x31) . chr(0x50) . chr(0x30); // Store data
        $commands[] = $data;

        // Print QR code
        $commands[] = chr(0x1D) . chr(0x28) . chr(0x6B) . chr(0x03) . chr(0x00) . chr(0x31) . chr(0x51) . chr(0x30); // Print QR

        return implode('', $commands);
    }

    private static function starLineQR(string $data): string
    {
        // StarLine QR (similar to ESC/POS)
        return self::escPosQR($data);
    }

    private static function zplQR(string $data): string
    {
        $commands = [];

        // QR code position
        $commands[] = "^FO300,800";

        // QR code size and data
        $commands[] = "^BQN,2,10";
        $commands[] = "^FDQA," . $data . "^FS";

        return implode('', $commands);
    }
}
