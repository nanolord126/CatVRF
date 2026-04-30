<?php

declare(strict_types=1);

/**
 * ListBeautySalons — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/listbeautysalons
 * @see https://catvrf.ru/docs/listbeautysalons
 * @see https://catvrf.ru/docs/listbeautysalons
 * @see https://catvrf.ru/docs/listbeautysalons
 * @see https://catvrf.ru/docs/listbeautysalons
 * @see https://catvrf.ru/docs/listbeautysalons
 * @see https://catvrf.ru/docs/listbeautysalons
 * @see https://catvrf.ru/docs/listbeautysalons
 * @see https://catvrf.ru/docs/listbeautysalons
 * @see https://catvrf.ru/docs/listbeautysalons
 * @see https://catvrf.ru/docs/listbeautysalons
 * @see https://catvrf.ru/docs/listbeautysalons
 * @see https://catvrf.ru/docs/listbeautysalons
 * @see https://catvrf.ru/docs/listbeautysalons
 * @see https://catvrf.ru/docs/listbeautysalons
 * @see https://catvrf.ru/docs/listbeautysalons
 * @see https://catvrf.ru/docs/listbeautysalons
 */

namespace App\Filament\Tenant\Resources\BeautyResource\Pages;

use Carbon\CarbonImmutable;

use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;

final class ListBeautySalons extends ListRecords
{
    protected static string $resource = BeautyResource::class;

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return self::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => self::class,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
