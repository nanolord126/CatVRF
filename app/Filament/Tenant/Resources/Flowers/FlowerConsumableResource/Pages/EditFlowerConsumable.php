<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Flowers\FlowerConsumableResource\Pages;

use App\Filament\Tenant\Resources\Flowers\FlowerConsumableResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditFlowerConsumable extends EditRecord
{
    protected static string $resource = FlowerConsumableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Расходный материал успешно обновлён';
    }
}
