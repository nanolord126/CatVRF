<?php

declare(strict_types=1);

namespace Modules\Analytics\Filament\Resources\DailyMetricsResource\Pages;

use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListDailyMetrics extends ListRecords
{
    protected static string $resource = \Modules\Analytics\Filament\Resources\DailyMetricsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
