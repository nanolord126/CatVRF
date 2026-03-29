<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\AutoParts\Pages;

use use App\Filament\Tenant\Resources\AutoPartsResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditAutoParts extends EditRecord
{
    protected static string $resource = AutoPartsResource::class;

    public function getTitle(): string
    {
        return 'Edit AutoParts';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}