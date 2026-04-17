<?php declare(strict_types=1);

namespace App\Domains\Flowers\Filament\Resources\BouquetResource\Pages;

use App\Domains\Flowers\Filament\Resources\BouquetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListBouquets extends ListRecords
{
    protected static string $resource = BouquetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
