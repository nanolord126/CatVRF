<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Filament\Resources\InventoryCheckResource\Pages;

use App\Domains\Inventory\Filament\Resources\InventoryCheckResource;
use Filament\Resources\Pages\EditRecord;

/**
 * Редактирование инвентаризации.
 */
final class EditInventoryCheck extends EditRecord
{
    protected static string $resource = InventoryCheckResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
