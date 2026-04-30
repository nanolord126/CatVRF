<?php

declare(strict_types=1);

namespace Modules\Dental\Filament\Resources\ToothChartResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Dental\Filament\Resources\ToothChartResource;

final class EditToothChart extends EditRecord
{
    protected static string $resource = ToothChartResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
