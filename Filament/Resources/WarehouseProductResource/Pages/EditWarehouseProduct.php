<?php

declare(strict_types=1);

namespace App\Filament\Resources\WarehouseProductResource\Pages;

use App\Filament\Resources\WarehouseProductResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditWarehouseProduct extends EditRecord
{
    protected static string $resource = WarehouseProductResource::class;

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
