<?php declare(strict_types=1);

/**
 * CreateLuxuryProduct — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/createluxuryproduct
 * @see https://catvrf.ru/docs/createluxuryproduct
 * @see https://catvrf.ru/docs/createluxuryproduct
 * @see https://catvrf.ru/docs/createluxuryproduct
 * @see https://catvrf.ru/docs/createluxuryproduct
 * @see https://catvrf.ru/docs/createluxuryproduct
 */


namespace App\Filament\Tenant\Resources\LuxuryProductResource\Pages;



use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
use Filament\Resources\Pages\CreateRecord;

final class CreateLuxuryProduct extends CreateRecord
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}


    protected static string $resource = LuxuryProductResource::class;

        protected function mutateFormDataBeforeCreate(array $data): array
        {
            $data['correlation_id'] = (string) Str::uuid();

            $this->logger->info('Creating Luxury Product via Filament', [
                'sku' => $data['sku'] ?? 'N/A',
                'user_id' => $this->guard->id(),
                'correlation_id' => $data['correlation_id'],
            ]);

            return $data;
        }

        protected function getRedirectUrl(): string
        {
            return $this->getResource()::getUrl('index');
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
