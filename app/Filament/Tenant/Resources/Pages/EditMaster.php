<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Master\Pages;

use use App\Filament\Tenant\Resources\MasterResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditMaster extends EditRecord
{
    protected static string $resource = MasterResource::class;

    public function getTitle(): string
    {
        return 'Edit Master';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}