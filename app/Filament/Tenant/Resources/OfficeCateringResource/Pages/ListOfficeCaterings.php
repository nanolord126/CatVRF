<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\OfficeCateringResource\Pages;

use App\Filament\Tenant\Resources\OfficeCateringResource;
use Filament\Resources\Pages\ListRecords;

final class ListOfficeCaterings extends ListRecords
{
    protected static string $resource = OfficeCateringResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
