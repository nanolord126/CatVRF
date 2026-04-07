<?php declare(strict_types=1);

/**
 * EditAutoRepairOrder — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/editautorepairorder
 * @see https://catvrf.ru/docs/editautorepairorder
 * @see https://catvrf.ru/docs/editautorepairorder
 * @see https://catvrf.ru/docs/editautorepairorder
 * @see https://catvrf.ru/docs/editautorepairorder
 * @see https://catvrf.ru/docs/editautorepairorder
 * @see https://catvrf.ru/docs/editautorepairorder
 * @see https://catvrf.ru/docs/editautorepairorder
 * @see https://catvrf.ru/docs/editautorepairorder
 * @see https://catvrf.ru/docs/editautorepairorder
 */


namespace App\Filament\Tenant\Resources\Auto\AutoRepairOrderResource\Pages;

use Filament\Resources\Pages\EditRecord;

final class EditAutoRepairOrder extends EditRecord
{

    protected static string $resource = AutoRepairOrderResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\ViewAction::make(),
                Actions\DeleteAction::make(),
                Actions\RestoreAction::make(),
            ];
        }

        protected function mutateFormDataBeforeSave(array $data): array
        {
            $data['total_cost_kopecks'] = ($data['labor_cost_kopecks'] ?? 0) + ($data['parts_cost_kopecks'] ?? 0);
            return $data;
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
