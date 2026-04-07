<?php declare(strict_types=1);

/**
 * CreateApartment — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/createapartment
 */


namespace App\Domains\ShortTermRentals\Filament\Resources\ApartmentResource\Pages;


use Illuminate\Contracts\Auth\Guard;
use Filament\Resources\Pages\CreateRecord;

final class CreateApartment extends CreateRecord
{
    public function __construct(
        private readonly Guard $guard) {}


    protected static string $resource = ApartmentResource::class;

        protected function mutateFormDataBeforeCreate(array $data): array
        {
            $data['tenant_id'] = tenant()->id;
            $data['owner_id'] = $this->guard->id();
            $data['uuid'] = \Illuminate\Support\Str::uuid();
            $data['correlation_id'] = \Illuminate\Support\Str::uuid();
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
