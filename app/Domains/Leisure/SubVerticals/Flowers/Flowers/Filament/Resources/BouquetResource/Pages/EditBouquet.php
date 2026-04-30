<?php

declare(strict_types=1);

namespace App\Domains\Leisure\SubVerticals\Flowers\Filament\Resources\BouquetResource\Pages;

use App\Domains\Flowers\Filament\Resources\BouquetResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * EditBouquet — CatVRF 2026 Component.
 *
 * Filament page for editing bouquets.
 * Tenant-scoped: all data filtered by current tenant.
 */
final class EditBouquet extends EditRecord
{
    protected static string $resource = BouquetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
