<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Auto\Pages;

use use App\Filament\Tenant\Resources\AutoResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditAuto extends EditRecord
{
    protected static string $resource = AutoResource::class;

    public function getTitle(): string
    {
        return 'Edit Auto';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}