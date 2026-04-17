<?php declare(strict_types=1);

namespace App\Domains\Food\Filament\Resources\ConsumableResource\Pages;

use App\Domains\Food\Filament\Resources\ConsumableResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditConsumable extends EditRecord
{
    protected static string $resource = ConsumableResource::class;

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
