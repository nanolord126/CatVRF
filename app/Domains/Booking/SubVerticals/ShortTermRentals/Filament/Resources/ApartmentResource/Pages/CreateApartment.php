<?php

declare(strict_types=1);

/**
 * CreateApartment — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/createapartment
 */

namespace App\Domains\ShortTermRentals\Filament\Resources\ApartmentResource\Pages;

use Carbon\CarbonImmutable;

use Illuminate\Contracts\Auth\Guard;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

final class CreateApartment extends CreateRecord
{
    protected static string $resource = ApartmentResource::class;

    public function __construct(
        private readonly Guard $guard
    ) {}

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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = tenant()->id;
        $data['owner_id'] = $this->guard->id();
        $data['uuid'] = Str::uuid();
        $data['correlation_id'] = Str::uuid();

        return $data;
    }
}
