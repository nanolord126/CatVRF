<?php

namespace App\Filament\Tenant\Resources\GymResource\Pages;

use App\Filament\Tenant\Resources\GymResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGyms extends ListRecords
{
    protected static string $resource = GymResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Tenant\Widgets\VerticalB2BRecommendationsWidget::class,
        ];
    }
}
