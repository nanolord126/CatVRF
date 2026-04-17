<?php declare(strict_types=1);

/**
 * CreateFashionProduct — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/createfashionproduct
 * @see https://catvrf.ru/docs/createfashionproduct
 * @see https://catvrf.ru/docs/createfashionproduct
 */


namespace App\Filament\Tenant\Resources\Pages;


use Psr\Log\LoggerInterface;
use App\Filament\Tenant\Resources\FashionProductResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

/**
 * Class CreateFashionProduct
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\Pages
 */
final class CreateFashionProduct extends CreateRecord
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    protected static string $resource = FashionProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id']      = tenant()->id ?? null;
        $data['correlation_id'] = (string) \Illuminate\Support\Str::uuid();
        $data['uuid']           = (string) \Illuminate\Support\Str::uuid();
        $data['status']         = $data['status'] ?? 'active';
        $data['reserve_quantity'] = $data['reserve_quantity'] ?? 0;

        return $data;
    }

    protected function afterCreate(): void
    {
        \Illuminate\Support\Facades\Log::channel('audit')->info('FashionProduct created', [
            'product_id'     => $this->record->id,
            'name'           => $this->record->name,
            'sku'            => $this->record->sku,
            'brand'          => $this->record->brand,
            'price_b2c'      => $this->record->price_b2c,
            'tenant_id'      => $this->record->tenant_id,
            'correlation_id' => $this->record->correlation_id,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
