<?php declare(strict_types=1);

namespace App\Domains\Tickets\Filament\Resources\TicketResource\Pages;

use App\Domains\Tickets\Filament\Resources\TicketResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

final class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
