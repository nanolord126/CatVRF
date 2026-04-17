<?php declare(strict_types=1);

namespace App\Domains\Tickets\Filament\Resources\TicketTypeResource\Pages;

use App\Domains\Tickets\Filament\Resources\TicketTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListTicketTypes extends ListRecords
{
    protected static string $resource = TicketTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
