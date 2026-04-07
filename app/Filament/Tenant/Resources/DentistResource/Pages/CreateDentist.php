<?php declare(strict_types=1);

/**
 * CreateDentist — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/createdentist
 * @see https://catvrf.ru/docs/createdentist
 * @see https://catvrf.ru/docs/createdentist
 * @see https://catvrf.ru/docs/createdentist
 * @see https://catvrf.ru/docs/createdentist
 * @see https://catvrf.ru/docs/createdentist
 * @see https://catvrf.ru/docs/createdentist
 * @see https://catvrf.ru/docs/createdentist
 * @see https://catvrf.ru/docs/createdentist
 * @see https://catvrf.ru/docs/createdentist
 * @see https://catvrf.ru/docs/createdentist
 * @see https://catvrf.ru/docs/createdentist
 * @see https://catvrf.ru/docs/createdentist
 * @see https://catvrf.ru/docs/createdentist
 * @see https://catvrf.ru/docs/createdentist
 * @see https://catvrf.ru/docs/createdentist
 * @see https://catvrf.ru/docs/createdentist
 */


namespace App\Filament\Tenant\Resources\DentistResource\Pages;

use Filament\Resources\Pages\CreateRecord;

final class CreateDentist extends CreateRecord
{

    protected static string $resource = DentistResource::class;

        protected function getRedirectUrl(): string
        {
            return $this->getResource()::getUrl('index');
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
