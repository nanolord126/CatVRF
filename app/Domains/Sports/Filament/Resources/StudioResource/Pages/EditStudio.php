<?php declare(strict_types=1);

namespace App\Domains\Sports\Filament\Resources\StudioResource\Pages;

use App\Domains\Sports\Filament\Resources\StudioResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditStudio extends EditRecord
{
    protected static string $resource = StudioResource::class;

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
