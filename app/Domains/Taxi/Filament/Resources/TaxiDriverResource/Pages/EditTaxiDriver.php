<?php declare(strict_types=1);

namespace App\Domains\Taxi\Filament\Resources\TaxiDriverResource\Pages;

use App\Domains\Taxi\Filament\Resources\TaxiDriverResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditTaxiDriver extends EditRecord
{
    protected static string $resource = TaxiDriverResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
