<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\CollectibleStore\Pages;

use use App\Filament\Tenant\Resources\CollectibleStoreResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditCollectibleStore extends EditRecord
{
    protected static string $resource = CollectibleStoreResource::class;

    public function getTitle(): string
    {
        return 'Edit CollectibleStore';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}