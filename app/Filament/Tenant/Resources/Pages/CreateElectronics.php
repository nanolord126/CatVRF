<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;



use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
use App\Filament\Tenant\Resources\ElectronicsResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Class CreateElectronics
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\Pages
 */
final class CreateElectronics extends CreateRecord
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    protected static string $resource = ElectronicsResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uuid']           = (string) Str::uuid();
        $data['correlation_id'] = (string) Str::uuid();
        $data['tenant_id']      = $this->guard->user()?->tenant_id;
        $data['status']         ??= 'active';
        $data['current_stock']  ??= 0;

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;
        \Illuminate\Support\Facades\Log::channel('audit')->info('Electronic product created', [
            'product_id'     => $record->id,
            'name'           => $record->name,
            'brand'          => $record->brand,
            'price'          => $record->price,
            'correlation_id' => $record->correlation_id,
            'tenant_id'      => $record->tenant_id,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
