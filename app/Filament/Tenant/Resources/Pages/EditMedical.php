<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Medical\Pages;

use use App\Filament\Tenant\Resources\MedicalResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditMedical extends EditRecord
{
    protected static string $resource = MedicalResource::class;

    public function getTitle(): string
    {
        return 'Edit Medical';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}