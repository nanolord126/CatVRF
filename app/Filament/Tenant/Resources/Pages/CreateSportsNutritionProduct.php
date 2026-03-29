<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\SportsNutritionProduct\Pages;

use use App\Filament\Tenant\Resources\SportsNutritionProductResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateSportsNutritionProduct extends CreateRecord
{
    protected static string $resource = SportsNutritionProductResource::class;

    public function getTitle(): string
    {
        return 'Create SportsNutritionProduct';
    }
}