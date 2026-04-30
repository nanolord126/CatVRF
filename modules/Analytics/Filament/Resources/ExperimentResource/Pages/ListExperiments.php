<?php

declare(strict_types=1);

namespace Modules\Analytics\Filament\Resources\ExperimentResource\Pages;

use Modules\Analytics\Filament\Resources\ExperimentResource;
use Filament\Resources\Pages\ListRecords;

class ListExperiments extends ListRecords
{
    protected static string $resource = ExperimentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Pages\Actions\CreateAction::make(),
        ];
    }
}
