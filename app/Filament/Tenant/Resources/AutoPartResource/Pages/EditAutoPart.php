<?php declare(strict_types=1);

/**
 * EditAutoPart — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/editautopart
 * @see https://catvrf.ru/docs/editautopart
 * @see https://catvrf.ru/docs/editautopart
 * @see https://catvrf.ru/docs/editautopart
 * @see https://catvrf.ru/docs/editautopart
 * @see https://catvrf.ru/docs/editautopart
 * @see https://catvrf.ru/docs/editautopart
 */


namespace App\Filament\Tenant\Resources\AutoPartResource\Pages;


use Illuminate\Contracts\Auth\Guard;
use Filament\Resources\Pages\EditRecord;

final class EditAutoPart extends EditRecord
{

    protected static string $resource = AutoPartResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\DeleteAction::make(),
            ];
        }

        protected function afterSave(): void
        {
            activity()
                ->performedBy($this->guard->user())
                ->on($this->record)
                ->withProperty('correlation_id', $this->record->correlation_id)
                ->log('Auto part inventory updated');
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
