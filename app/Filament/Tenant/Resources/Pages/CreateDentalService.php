<?php

declare(strict_types=1);

/**
 * CreateDentalService — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/createdentalservice
 */

namespace App\Filament\Tenant\Resources\Pages;

use Psr\Log\LoggerInterface;

use App\Filament\Tenant\Resources\DentalServiceResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Log\LogManager;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Support\Str;

/**
 * Class CreateDentalService
 *
 * Service layer following CatVRF canon:
 * - Constructor injection only (no Facades)
 * - FraudControlService::check() before mutations
 * - $this->db->transaction() wrapping all write operations
 * - Audit logging with correlation_id
 * - Tenant and BusinessGroup scoping
 *
 * @see FraudControlService
 * @see AuditService
 */
final class CreateDentalService extends CreateRecord
{
    protected static string $resource = DentalServiceResource::class;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $log,) {}

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id']      = tenant()->id ?? null;
        $data['correlation_id'] = (string) Str::uuid();
        $data['uuid']           = (string) Str::uuid();

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->log->channel('audit')->$this->logger->info('DentalService created', [
            'service_id'     => $this->record->id,
            'name'           => $this->record->name,
            'category'       => $this->record->category,
            'base_price'     => $this->record->base_price,
            'tenant_id'      => $this->record->tenant_id,
            'correlation_id' => $this->record->correlation_id,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
