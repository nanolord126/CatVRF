<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Tickets\Pages;

use use App\Filament\Tenant\Resources\TicketsResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditTickets extends EditRecord
{
    protected static string $resource = TicketsResource::class;

    public function getTitle(): string
    {
        return 'Edit Tickets';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}