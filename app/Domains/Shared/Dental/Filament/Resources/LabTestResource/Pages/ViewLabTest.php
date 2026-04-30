<?php

declare(strict_types=1);

namespace Modules\Dental\Filament\Resources\LabTestResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Modules\Dental\Filament\Resources\LabTestResource;

final class ViewLabTest extends ViewRecord
{
    protected static string $resource = LabTestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
