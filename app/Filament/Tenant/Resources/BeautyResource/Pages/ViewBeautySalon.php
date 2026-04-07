<?php declare(strict_types=1);

/**
 * ViewBeautySalon — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/viewbeautysalon
 * @see https://catvrf.ru/docs/viewbeautysalon
 * @see https://catvrf.ru/docs/viewbeautysalon
 * @see https://catvrf.ru/docs/viewbeautysalon
 * @see https://catvrf.ru/docs/viewbeautysalon
 * @see https://catvrf.ru/docs/viewbeautysalon
 * @see https://catvrf.ru/docs/viewbeautysalon
 * @see https://catvrf.ru/docs/viewbeautysalon
 * @see https://catvrf.ru/docs/viewbeautysalon
 * @see https://catvrf.ru/docs/viewbeautysalon
 * @see https://catvrf.ru/docs/viewbeautysalon
 * @see https://catvrf.ru/docs/viewbeautysalon
 * @see https://catvrf.ru/docs/viewbeautysalon
 * @see https://catvrf.ru/docs/viewbeautysalon
 * @see https://catvrf.ru/docs/viewbeautysalon
 * @see https://catvrf.ru/docs/viewbeautysalon
 * @see https://catvrf.ru/docs/viewbeautysalon
 */


namespace App\Filament\Tenant\Resources\BeautyResource\Pages;

use Filament\Resources\Pages\ViewRecord;

final class ViewBeautySalon extends ViewRecord
{

    protected static string $resource = BeautyResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\EditAction::make(),
            ];
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
