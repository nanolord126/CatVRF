<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\SportsNutritionProduct\Pages;

use use App\Filament\Tenant\Resources\SportsNutritionProductResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditSportsNutritionProduct extends EditRecord
{
    protected static string $resource = SportsNutritionProductResource::class;

    public function getTitle(): string
    {
        return 'Edit SportsNutritionProduct';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}