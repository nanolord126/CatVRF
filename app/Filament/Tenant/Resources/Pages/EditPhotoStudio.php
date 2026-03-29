<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\PhotoStudio\Pages;

use use App\Filament\Tenant\Resources\PhotoStudioResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditPhotoStudio extends EditRecord
{
    protected static string $resource = PhotoStudioResource::class;

    public function getTitle(): string
    {
        return 'Edit PhotoStudio';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}