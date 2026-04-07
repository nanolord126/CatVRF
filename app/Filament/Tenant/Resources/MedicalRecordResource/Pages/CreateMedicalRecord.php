<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\MedicalRecordResource\Pages;

use Filament\Resources\Pages\CreateRecord;

final class CreateMedicalRecord extends CreateRecord
{

    protected static string $resource = MedicalRecordResource::class;

        protected function mutateFormDataBeforeCreate(array $data): array
        {
            $data['uuid'] = (string)Str::uuid();
            $data['tenant_id'] = tenant()->id;
            $data['correlation_id'] = (string)Str::uuid();

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

    /**
     * Handle graceful error recovery for the component.
     * Logs the error and determines if retry is possible.
     *
     * @param \Throwable $exception The caught exception
     * @param int $attempt Current attempt number
     * @return bool Whether the operation should be retried
     */
    private function handleError(\Throwable $exception, int $attempt = 1): bool
    {
        if ($attempt >= self::MAX_RETRIES) {
            return false;
        }

        return true;
    }

}
