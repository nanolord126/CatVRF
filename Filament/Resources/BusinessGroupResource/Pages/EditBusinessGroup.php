<?php

declare(strict_types=1);

namespace App\Filament\Resources\BusinessGroupResource\Pages;

use App\Filament\Resources\BusinessGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditBusinessGroup extends EditRecord
{
    protected static string $resource = BusinessGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
