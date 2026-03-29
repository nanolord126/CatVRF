<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\MeatShops\Pages;

use use App\Filament\Tenant\Resources\MeatShopsResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditMeatShops extends EditRecord
{
    protected static string $resource = MeatShopsResource::class;

    public function getTitle(): string
    {
        return 'Edit MeatShops';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}