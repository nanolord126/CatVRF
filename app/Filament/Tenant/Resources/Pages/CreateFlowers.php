<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;

use App\Filament\Tenant\Resources\FlowersResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Class CreateFlowers
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\Pages
 */
final class CreateFlowers extends CreateRecord
{
    protected static string $resource = FlowersResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['correlation_id'] = (string) Str::uuid();
        $data['tenant_id']      = filament()->getTenant()?->id;
        $data['is_active']      ??= true;
        $data['is_verified']    ??= false;

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;
        $logger = app(LoggerInterface::class);
        $logger->info('Flower B2B storefront created', [
            'storefront_id'  => $record->id,
            'company_name'   => $record->company_name,
            'company_inn'    => $record->company_inn,
            'correlation_id' => $record->correlation_id,
            'tenant_id'      => $record->tenant_id,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
