<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Filament\Resources\PropertyResource\Pages;

use App\Domains\RealEstate\Filament\Resources\PropertyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListProperties extends ListRecords
{
    protected static string $resource = PropertyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
