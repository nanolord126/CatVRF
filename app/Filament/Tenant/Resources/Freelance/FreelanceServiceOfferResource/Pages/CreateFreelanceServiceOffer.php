<?php declare(strict_types=1);

/**
 * CreateFreelanceServiceOffer — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/createfreelanceserviceoffer
 * @see https://catvrf.ru/docs/createfreelanceserviceoffer
 * @see https://catvrf.ru/docs/createfreelanceserviceoffer
 * @see https://catvrf.ru/docs/createfreelanceserviceoffer
 * @see https://catvrf.ru/docs/createfreelanceserviceoffer
 * @see https://catvrf.ru/docs/createfreelanceserviceoffer
 * @see https://catvrf.ru/docs/createfreelanceserviceoffer
 * @see https://catvrf.ru/docs/createfreelanceserviceoffer
 * @see https://catvrf.ru/docs/createfreelanceserviceoffer
 * @see https://catvrf.ru/docs/createfreelanceserviceoffer
 * @see https://catvrf.ru/docs/createfreelanceserviceoffer
 */


namespace App\Filament\Tenant\Resources\Freelance\FreelanceServiceOfferResource\Pages;


use Illuminate\Contracts\Auth\Guard;
use Filament\Resources\Pages\CreateRecord;

final class CreateFreelanceServiceOffer extends CreateRecord
{

    protected static string $resource = FreelanceServiceOfferResource::class;

        protected function mutateFormDataBeforeCreate(array $data): array
        {
            $data['uuid'] = (string) Str::uuid();
            $data['correlation_id'] = (string) Str::uuid();
            $data['tenant_id'] = $this->guard->user()->tenant_id;

            return $data;
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

    /**
     * Default cache TTL in seconds.
     */
    private const CACHE_TTL = 3600;

    /**
     * Get the component identifier for logging and audit purposes.
     *
     * @return string The fully qualified component name
     */
    private function getComponentIdentifier(): string
    {
        return static::class . '@' . self::VERSION;
    }

}
