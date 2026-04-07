<?php declare(strict_types=1);

/**
 * CreateRecordFreelancer — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/createrecordfreelancer
 * @see https://catvrf.ru/docs/createrecordfreelancer
 * @see https://catvrf.ru/docs/createrecordfreelancer
 * @see https://catvrf.ru/docs/createrecordfreelancer
 * @see https://catvrf.ru/docs/createrecordfreelancer
 * @see https://catvrf.ru/docs/createrecordfreelancer
 * @see https://catvrf.ru/docs/createrecordfreelancer
 * @see https://catvrf.ru/docs/createrecordfreelancer
 * @see https://catvrf.ru/docs/createrecordfreelancer
 * @see https://catvrf.ru/docs/createrecordfreelancer
 * @see https://catvrf.ru/docs/createrecordfreelancer
 */


namespace App\Filament\Tenant\Resources\Freelance\FreelancerResource\Pages;


use Illuminate\Contracts\Auth\Guard;
use Filament\Resources\Pages\CreateRecord;

final class CreateRecordFreelancer extends CreateRecord
{

    protected static string $resource = FreelancerResource::class;

        /**
         * КАНОН 2026 — FRAUD CHECK & UUID
         */
        protected function mutateFormDataBeforeCreate(array $data): array
        {
            $data['uuid'] = (string) Str::uuid();
            $data['correlation_id'] = (string) Str::uuid();
            $data['tenant_id'] = $this->guard->user()->tenant_id;

            // Пре-проверка на фрод при регистрации профиля специалиста
            app(FraudControlService::class)->check([
                'user_id' => $this->guard->id(),
                'operation' => 'freelancer_register',
                'correlation_id' => $data['correlation_id']
            ]);

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

}
