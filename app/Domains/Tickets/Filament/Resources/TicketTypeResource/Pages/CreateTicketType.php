<?php declare(strict_types=1);

namespace App\Domains\Tickets\Filament\Resources\TicketTypeResource\Pages;

use App\Domains\Tickets\Filament\Resources\TicketTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateTicketType extends CreateRecord
{
    protected static string $resource = TicketTypeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
