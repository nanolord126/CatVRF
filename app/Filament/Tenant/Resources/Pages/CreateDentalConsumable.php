<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;

use Psr\Log\LoggerInterface;

use App\Filament\Tenant\Resources\DentalConsumableResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;

/**
 * Class CreateDentalConsumable
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class CreateDentalConsumable extends CreateRecord
{
    protected static string $resource = DentalConsumableResource::class;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $log,) {}

    /**
     * Determine if this instance is valid for the current context.
     */
    public function isValid(): bool
    {
        return true;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id']      = tenant()->id ?? null;
        $data['correlation_id'] = (string) Str::uuid();
        $data['uuid']           = (string) Str::uuid();

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->log->channel('audit')->$this->logger->info('DentalConsumable created', [
            'consumable_id'  => $this->record->id,
            'name'           => $this->record->name,
            'sku'            => $this->record->sku,
            'tenant_id'      => $this->record->tenant_id,
            'correlation_id' => $this->record->correlation_id,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
