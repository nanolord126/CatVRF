<?php

namespace App\Filament\Tenant\Resources\BeautySalonResource\Pages;

use App\Filament\Tenant\Resources\BeautySalonResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBeautySalons extends ListRecords
{
    protected static string $resource = BeautySalonResource::class;

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
