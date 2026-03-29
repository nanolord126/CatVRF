<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\AutoPart\Pages;

use use App\Filament\Tenant\Resources\AutoPartResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditAutoPart extends EditRecord
{
    protected static string $resource = AutoPartResource::class;

    public function getTitle(): string
    {
        return 'Edit AutoPart';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}