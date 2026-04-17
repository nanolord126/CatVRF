<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Filament\Resources\MortgageApplicationResource\Pages;

use App\Domains\RealEstate\Filament\Resources\MortgageApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListMortgageApplications extends ListRecords
{
    protected static string $resource = MortgageApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
