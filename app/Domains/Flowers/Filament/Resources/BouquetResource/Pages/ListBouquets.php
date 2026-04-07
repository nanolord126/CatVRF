<?php declare(strict_types=1);

namespace App\Domains\Flowers\Filament\Resources\BouquetResource\Pages;

use App\Domains\Flowers\Filament\Resources\BouquetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

/**
 * ListBouquets — CatVRF 2026 Component.
 *
 * Filament page for listing bouquets.
 * Tenant-scoped: all data filtered by current tenant.
 *
 * @package App\Domains\Flowers\Filament\Resources\BouquetResource\Pages
 */
final class ListBouquets extends ListRecords
{
    protected static string $resource = BouquetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getTableQuery(): ?Builder
    {
        return parent::getTableQuery()
            ?->where('tenant_id', filament()->getTenant()?->id);
    }
}
