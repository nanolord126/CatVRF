<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Food\Pages;

use use App\Filament\Tenant\Resources\FoodResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditFood extends EditRecord
{
    protected static string $resource = FoodResource::class;

    public function getTitle(): string
    {
        return 'Edit Food';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}