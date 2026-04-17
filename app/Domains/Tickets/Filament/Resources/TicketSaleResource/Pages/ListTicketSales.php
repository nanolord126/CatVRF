<?php declare(strict_types=1);

namespace App\Domains\Tickets\Filament\Resources\TicketSaleResource\Pages;

use App\Domains\Tickets\Filament\Resources\TicketSaleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListTicketSales extends ListRecords
{
    protected static string $resource = TicketSaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
