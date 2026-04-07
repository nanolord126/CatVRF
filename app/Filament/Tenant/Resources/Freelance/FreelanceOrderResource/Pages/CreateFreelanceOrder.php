<?php declare(strict_types=1);

/**
 * CreateFreelanceOrder — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/createfreelanceorder
 * @see https://catvrf.ru/docs/createfreelanceorder
 * @see https://catvrf.ru/docs/createfreelanceorder
 * @see https://catvrf.ru/docs/createfreelanceorder
 * @see https://catvrf.ru/docs/createfreelanceorder
 * @see https://catvrf.ru/docs/createfreelanceorder
 * @see https://catvrf.ru/docs/createfreelanceorder
 * @see https://catvrf.ru/docs/createfreelanceorder
 * @see https://catvrf.ru/docs/createfreelanceorder
 */


namespace App\Filament\Tenant\Resources\Freelance\FreelanceOrderResource\Pages;


use Illuminate\Contracts\Auth\Guard;
use Filament\Resources\Pages\CreateRecord;

final class CreateFreelanceOrder extends CreateRecord
{

    protected static string $resource = FreelanceOrderResource::class;

        /**
         * КАНОН 2026 — Использование сервиса для создания заказа
         */
        protected function handleRecordCreation(array $data): FreelanceOrder
        {
            $data['correlation_id'] = (string) Str::uuid();
            $data['tenant_id'] = $this->guard->user()->tenant_id;

            return app(FreelanceService::class)->createOrder($data);
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
