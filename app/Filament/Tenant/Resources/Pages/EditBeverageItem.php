<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeverageItem\Pages;

use use App\Filament\Tenant\Resources\BeverageItemResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditBeverageItem extends EditRecord
{
    protected static string $resource = BeverageItemResource::class;

    public function getTitle(): string
    {
        return 'Edit BeverageItem';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}