<?php

declare(strict_types=1);

namespace App\Filament\Resources\WarehouseBatchResource\Pages;

use App\Filament\Resources\WarehouseBatchResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditWarehouseBatch extends EditRecord
{
    protected static string $resource = WarehouseBatchResource::class;

    protected function getActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\SaveAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
