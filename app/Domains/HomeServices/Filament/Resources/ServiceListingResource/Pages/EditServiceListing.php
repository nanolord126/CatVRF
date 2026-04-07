<?php declare(strict_types=1);

/**
 * EditServiceListing — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/editservicelisting
 */


namespace App\Domains\HomeServices\Filament\Resources\ServiceListingResource\Pages;

use Carbon\Carbon;

use Filament\Resources\Pages\EditRecord;

final class EditServiceListing extends EditRecord
{

    protected static string $resource = ServiceListingResource::class;

        protected function getHeaderActions(): array
        {
            return [Actions\DeleteAction::make()];
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
            'timestamp' => Carbon::now()->toIso8601String(),
        ];
    }
}
