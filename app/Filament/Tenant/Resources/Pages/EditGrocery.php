<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Grocery\Pages;

use use App\Filament\Tenant\Resources\GroceryResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditGrocery extends EditRecord
{
    protected static string $resource = GroceryResource::class;

    public function getTitle(): string
    {
        return 'Edit Grocery';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}