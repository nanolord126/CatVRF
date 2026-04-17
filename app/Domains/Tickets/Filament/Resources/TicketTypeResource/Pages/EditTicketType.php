<?php declare(strict_types=1);

namespace App\Domains\Tickets\Filament\Resources\TicketTypeResource\Pages;

use App\Domains\Tickets\Filament\Resources\TicketTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditTicketType extends EditRecord
{
    protected static string $resource = TicketTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
