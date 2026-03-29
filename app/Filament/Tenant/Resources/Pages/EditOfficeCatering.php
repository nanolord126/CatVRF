<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\OfficeCatering\Pages;

use use App\Filament\Tenant\Resources\OfficeCateringResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditOfficeCatering extends EditRecord
{
    protected static string $resource = OfficeCateringResource::class;

    public function getTitle(): string
    {
        return 'Edit OfficeCatering';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}