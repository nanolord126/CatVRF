<?php declare(strict_types=1);

namespace App\Domains\Tickets\Filament\Resources\EventResource\Pages;

use App\Domains\Tickets\Filament\Resources\EventResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;

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
