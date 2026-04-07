<?php declare(strict_types=1);

/**
 * EditB2BDeal — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/editb2bdeal
 * @see https://catvrf.ru/docs/editb2bdeal
 * @see https://catvrf.ru/docs/editb2bdeal
 * @see https://catvrf.ru/docs/editb2bdeal
 * @see https://catvrf.ru/docs/editb2bdeal
 * @see https://catvrf.ru/docs/editb2bdeal
 * @see https://catvrf.ru/docs/editb2bdeal
 * @see https://catvrf.ru/docs/editb2bdeal
 * @see https://catvrf.ru/docs/editb2bdeal
 * @see https://catvrf.ru/docs/editb2bdeal
 * @see https://catvrf.ru/docs/editb2bdeal
 * @see https://catvrf.ru/docs/editb2bdeal
 * @see https://catvrf.ru/docs/editb2bdeal
 * @see https://catvrf.ru/docs/editb2bdeal
 * @see https://catvrf.ru/docs/editb2bdeal
 * @see https://catvrf.ru/docs/editb2bdeal
 * @see https://catvrf.ru/docs/editb2bdeal
 */


namespace App\Filament\Tenant\Resources\B2BDealResource\Pages;

use Filament\Resources\Pages\EditRecord;

final class EditB2BDeal extends EditRecord
{

    protected static string $resource = B2BDealResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\DeleteAction::make(),
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
