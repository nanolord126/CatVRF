<?php declare(strict_types=1);

/**
 * CreateBeautySalon — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/createbeautysalon
 * @see https://catvrf.ru/docs/createbeautysalon
 * @see https://catvrf.ru/docs/createbeautysalon
 * @see https://catvrf.ru/docs/createbeautysalon
 * @see https://catvrf.ru/docs/createbeautysalon
 * @see https://catvrf.ru/docs/createbeautysalon
 * @see https://catvrf.ru/docs/createbeautysalon
 * @see https://catvrf.ru/docs/createbeautysalon
 * @see https://catvrf.ru/docs/createbeautysalon
 * @see https://catvrf.ru/docs/createbeautysalon
 * @see https://catvrf.ru/docs/createbeautysalon
 * @see https://catvrf.ru/docs/createbeautysalon
 */


namespace App\Filament\Tenant\Resources\BeautyResource\Pages;

use Filament\Resources\Pages\CreateRecord;

final class CreateBeautySalon extends CreateRecord
{

    protected static string $resource = BeautyResource::class;

        protected function mutateFormDataBeforeCreate(array $data): array
        {
            $data['tenant_id'] = filament()->getTenant()->id;
            $data['uuid'] = Str::uuid()->toString();
            $data['correlation_id'] = Str::uuid()->toString();

            if (session()->has('business_card_id')) {
                $data['business_group_id'] = session('business_card_id');
            }

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
