<?php

namespace App\Filament\B2B\Resources\B2BRecommendationResource;

use App\Filament\B2B\Resources\B2BRecommendationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListB2BRecommendations extends ListRecords
{
    protected static string $resource = B2BRecommendationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
