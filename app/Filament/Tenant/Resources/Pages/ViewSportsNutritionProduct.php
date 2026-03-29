<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\SportsNutritionProduct\Pages;

use use App\Filament\Tenant\Resources\SportsNutritionProductResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewSportsNutritionProduct extends ViewRecord
{
    protected static string $resource = SportsNutritionProductResource::class;

    public function getTitle(): string
    {
        return 'View SportsNutritionProduct';
    }
}