<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\ValueObjects;

use Modules\Warehouse\Domain\Enums\OrderTypeEnum;

final readonly class OrderColor
{
    private string $hexColor;
    private OrderTypeEnum $orderType;
    private int $brightnessLevel;

    public function __construct(string $hexColor, OrderTypeEnum $orderType)
    {
        $this->hexColor = $this->validateHexColor($hexColor);
        $this->orderType = $orderType;
        $this->brightnessLevel = $this->calculateBrightness($hexColor);
    }

    public static function forOrderType(OrderTypeEnum $orderType): self
    {
        $baseColor = $orderType->getDefaultColor();
        
        return new self($baseColor, $orderType);
    }

    public static function withBrightnessAdjustment(
        string $hexColor, 
        OrderTypeEnum $orderType, 
        int $adjustment = 17
    ): self {
        $adjustedColor = self::adjustBrightness($hexColor, $adjustment);
        
        return new self($adjustedColor, $orderType);
    }

    public function getHexColor(): string
    {
        return $this->hexColor;
    }

    public function getOrderType(): OrderTypeEnum
    {
        return $this->orderType;
    }

    public function getBrightnessLevel(): int
    {
        return $this->brightnessLevel;
    }

    public function isBrighterThan(OrderColor $other): bool
    {
        return $this->brightnessLevel > $other->brightnessLevel;
    }

    public function getBrightnessDifference(OrderColor $other): int
    {
        return abs($this->brightnessLevel - $other->brightnessLevel);
    }

    private function validateHexColor(string $color): string
    {
        if (!preg_match('/^#?([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color)) {
            throw new \InvalidArgumentException('Invalid hex color format');
        }

        return strpos($color, '#') === 0 ? $color : '#' . $color;
    }

    private function calculateBrightness(string $hexColor): int
    {
        $hex = ltrim($hexColor, '#');
        
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return (int) (($r * 299 + $g * 587 + $b * 114) / 1000);
    }

    private static function adjustBrightness(string $hexColor, int $amount): string
    {
        $hex = ltrim($hexColor, '#');
        
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        $r = max(0, min(255, hexdec(substr($hex, 0, 2)) + $amount));
        $g = max(0, min(255, hexdec(substr($hex, 2, 2)) + $amount));
        $b = max(0, min(255, hexdec(substr($hex, 4, 2)) + $amount));

        return sprintf('#%02X%02X%02X', $r, $g, $b);
    }

    public function toArray(): array
    {
        return [
            'hex_color' => $this->hexColor,
            'order_type' => $this->orderType->value,
            'brightness_level' => $this->brightnessLevel,
        ];
    }
}
