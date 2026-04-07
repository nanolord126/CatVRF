<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Flowers\FlowerProductResource\Pages;

use App\Filament\Tenant\Resources\Flowers\FlowerProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditFlowerProduct extends EditRecord
{
    protected static string $resource = FlowerProductResource::class;

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
        return 'Цветочный товар успешно обновлён';
    }
}
