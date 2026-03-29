<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\TaxiDriver\Pages;

use use App\Filament\Tenant\Resources\TaxiDriverResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditTaxiDriver extends EditRecord
{
    protected static string $resource = TaxiDriverResource::class;

    public function getTitle(): string
    {
        return 'Edit TaxiDriver';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}