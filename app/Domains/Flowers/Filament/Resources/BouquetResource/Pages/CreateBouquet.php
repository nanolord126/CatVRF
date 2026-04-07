<?php declare(strict_types=1);

namespace App\Domains\Flowers\Filament\Resources\BouquetResource\Pages;

use App\Domains\Flowers\Filament\Resources\BouquetResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

/**
 * CreateBouquet — CatVRF 2026 Component.
 *
 * Filament page for creating bouquets.
 * Tenant-scoped: all data filtered by current tenant.
 *
 * @package App\Domains\Flowers\Filament\Resources\BouquetResource\Pages
 */
final class CreateBouquet extends CreateRecord
{
    protected static string $resource = BouquetResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = filament()->getTenant()?->id;
        $data['uuid'] = (string) Str::uuid();
        $data['correlation_id'] = (string) Str::uuid();
        return $data;
    }
}
