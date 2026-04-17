<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;



use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
use App\Filament\Tenant\Resources\FoodResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Class CreateFood
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\Pages
 */
final class CreateFood extends CreateRecord
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    protected static string $resource = FoodResource::class;

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
        \Illuminate\Support\Facades\Log::channel('audit')->info('Food order created', [
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
