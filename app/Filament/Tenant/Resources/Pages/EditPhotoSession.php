<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\PhotoSession\Pages;

use use App\Filament\Tenant\Resources\PhotoSessionResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditPhotoSession extends EditRecord
{
    protected static string $resource = PhotoSessionResource::class;

    public function getTitle(): string
    {
        return 'Edit PhotoSession';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}