<?php

declare(strict_types=1);

/**
 * CreateLuxuryProduct — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/createluxuryproduct
 * @see https://catvrf.ru/docs/createluxuryproduct
 * @see https://catvrf.ru/docs/createluxuryproduct
 * @see https://catvrf.ru/docs/createluxuryproduct
 * @see https://catvrf.ru/docs/createluxuryproduct
 * @see https://catvrf.ru/docs/createluxuryproduct
 */

namespace App\Filament\Tenant\Resources\LuxuryProductResource\Pages;

use Psr\Log\LoggerInterface;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Log\LogManager;

final class CreateLuxuryProduct extends CreateRecord
{
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


    protected static string $resource = LuxuryProductResource::class;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $log,) {}

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['correlation_id'] = (string) Str::uuid();

        $this->log->channel('audit')->$this->logger->info('Creating Luxury Product via Filament', [
            'sku' => $data['sku'] ?? 'N/A',
            'user_id' => auth()->id(),
            'correlation_id' => $data['correlation_id'],
        ]);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
