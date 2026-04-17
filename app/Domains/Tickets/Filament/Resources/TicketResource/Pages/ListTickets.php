<?php declare(strict_types=1);

namespace App\Domains\Tickets\Filament\Resources\TicketResource\Pages;

use App\Domains\Tickets\Filament\Resources\TicketResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListTickets extends ListRecords
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
