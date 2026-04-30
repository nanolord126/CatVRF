<?php

declare(strict_types=1);

namespace Modules\Dental\Filament\Resources\LabTestResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Dental\Filament\Resources\LabTestResource;

final class EditLabTest extends EditRecord
{
    protected static string $resource = LabTestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
