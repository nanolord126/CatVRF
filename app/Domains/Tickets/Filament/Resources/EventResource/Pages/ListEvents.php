<?php declare(strict_types=1);

namespace App\Domains\Tickets\Filament\Resources\EventResource\Pages;

use App\Domains\Tickets\Filament\Resources\EventResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListEvents extends ListRecords
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
