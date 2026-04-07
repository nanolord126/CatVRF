<?php declare(strict_types=1);

/**
 * CreateFarmDirect — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/createfarmdirect
 * @see https://catvrf.ru/docs/createfarmdirect
 * @see https://catvrf.ru/docs/createfarmdirect
 * @see https://catvrf.ru/docs/createfarmdirect
 */


namespace App\Filament\Tenant\Resources\Pages;



use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
use App\Filament\Tenant\Resources\FarmDirectResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Class CreateFarmDirect
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\Pages
 */
final class CreateFarmDirect extends CreateRecord
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    protected static string $resource = FarmDirectResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uuid']           = (string) Str::uuid();
        $data['correlation_id'] = (string) Str::uuid();
        $data['tenant_id']      = $this->guard->user()?->tenant_id;

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;
        $this->logger->info('Farm product created', [
            'product_id'     => $record->id,
            'name'           => $record->name,
            'correlation_id' => $record->correlation_id,
            'tenant_id'      => $record->tenant_id,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
