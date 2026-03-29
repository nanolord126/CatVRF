<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\MeatShop\Pages;

use use App\Filament\Tenant\Resources\MeatShopResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditMeatShop extends EditRecord
{
    protected static string $resource = MeatShopResource::class;

    public function getTitle(): string
    {
        return 'Edit MeatShop';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}