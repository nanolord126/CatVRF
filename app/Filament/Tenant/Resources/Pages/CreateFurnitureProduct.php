<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;




use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
use App\Filament\Tenant\Resources\FurnitureProductResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Class CreateFurnitureProduct
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\Pages
 */
final class CreateFurnitureProduct extends CreateRecord
{
    public function __construct(
        private readonly Request $request,
        private readonly LoggerInterface $logger,
    ) {}

    protected static string $resource = FurnitureProductResource::class;

    public function getTitle(): string
    {
        return 'Создание товара мебели';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uuid'] = $data['uuid'] ?? (string) Str::uuid();
        $data['correlation_id'] = $data['correlation_id'] ?? (string) ($this->request->header('X-Correlation-ID') ?? Str::uuid());
        $data['tenant_id'] = $data['tenant_id'] ?? $this->guard->user()?->tenant_id;
        $data['stock_quantity'] = $data['stock_quantity'] ?? 0;
        $data['hold_stock'] = $data['hold_stock'] ?? 0;
        $data['is_oversized'] = $data['is_oversized'] ?? false;
        $data['requires_assembly'] = $data['requires_assembly'] ?? false;

        return $data;
    }

    protected function afterCreate(): void
    {
        \Illuminate\Support\Facades\Log::channel('audit')->info('Furniture product created', [
            'product_id' => $this->record->id ?? null,
            'tenant_id' => $this->record->tenant_id ?? null,
            'correlation_id' => $this->record->correlation_id ?? null,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
