<?php declare(strict_types=1);

namespace App\Domains\Entertainment\Filament\Resources\TicketSaleResource\Pages;

use App\Domains\Entertainment\Filament\Resources\TicketSaleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditTicketSale extends EditRecord
{
    protected static string $resource = TicketSaleResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
