<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Flowers\BouquetResource\Pages;

use App\Filament\Tenant\Resources\Flowers\BouquetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditBouquet extends EditRecord
{
    protected static string $resource = BouquetResource::class;

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
        return 'Букет успешно обновлён';
    }
}
