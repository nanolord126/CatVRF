<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;

use Psr\Log\LoggerInterface;

use App\Filament\Tenant\Resources\FoodResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;

/**
 * Class CreateFood
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class CreateFood extends CreateRecord
{
    protected static string $resource = FoodResource::class;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $log,) {}

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['correlation_id'] = (string) Str::uuid();
        $data['tenant_id']      = $this->guard->user()?->tenant_id;
        $data['status']         ??= 'pending';
        $data['currency']       ??= 'RUB';

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;
        $this->log->channel('audit')->$this->logger->info('Food order created', [
            'order_id'       => $record->id,
            'restaurant_id'  => $record->restaurant_id,
            'total_price'    => $record->total_price,
            'correlation_id' => $record->correlation_id,
            'tenant_id'      => $record->tenant_id,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
