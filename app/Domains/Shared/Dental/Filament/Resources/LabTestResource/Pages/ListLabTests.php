<?php

declare(strict_types=1);

namespace Modules\Dental\Filament\Resources\LabTestResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Dental\Filament\Resources\LabTestResource;

final class ListLabTests extends ListRecords
{
    protected static string $resource = LabTestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
