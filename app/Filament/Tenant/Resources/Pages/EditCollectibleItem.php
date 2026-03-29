<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\CollectibleItem\Pages;

use use App\Filament\Tenant\Resources\CollectibleItemResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditCollectibleItem extends EditRecord
{
    protected static string $resource = CollectibleItemResource::class;

    public function getTitle(): string
    {
        return 'Edit CollectibleItem';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}