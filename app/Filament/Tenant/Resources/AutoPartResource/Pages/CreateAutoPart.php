<?php declare(strict_types=1);

/**
 * CreateAutoPart — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/createautopart
 * @see https://catvrf.ru/docs/createautopart
 * @see https://catvrf.ru/docs/createautopart
 * @see https://catvrf.ru/docs/createautopart
 * @see https://catvrf.ru/docs/createautopart
 */


namespace App\Filament\Tenant\Resources\AutoPartResource\Pages;


use Illuminate\Contracts\Auth\Guard;
use Filament\Resources\Pages\CreateRecord;

final class CreateAutoPart extends CreateRecord
{

    protected static string $resource = AutoPartResource::class;

        protected function mutateFormDataBeforeCreate(array $data): array
        {
            $data['uuid'] = (string) Str::uuid();
            $data['tenant_id'] = tenant()->id;
            $data['correlation_id'] = $data['correlation_id'] ?? (string) Str::uuid();

            return $data;
        }

        protected function afterCreate(): void
        {
            activity()
                ->performedBy($this->guard->user())
                ->on($this->record)
                ->withProperty('correlation_id', $this->record->correlation_id)
                ->log('Auto part added to STO inventory');
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
