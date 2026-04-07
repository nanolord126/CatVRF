<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Filament\Resources\AnalyticsEventResource\Pages;

use App\Domains\Analytics\Filament\Resources\AnalyticsEventResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListAnalyticsEvents extends ListRecords
{
    protected static string $resource = AnalyticsEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
