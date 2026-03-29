<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeverageShop\Pages;

use use App\Filament\Tenant\Resources\BeverageShopResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditBeverageShop extends EditRecord
{
    protected static string $resource = BeverageShopResource::class;

    public function getTitle(): string
    {
        return 'Edit BeverageShop';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}