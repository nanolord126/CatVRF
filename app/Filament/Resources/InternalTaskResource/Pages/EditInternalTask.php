<?php

declare(strict_types=1);

namespace App\Filament\Resources\InternalTaskResource\Pages;

use App\Filament\Resources\InternalTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditInternalTask extends EditRecord
{
    protected static string $resource = InternalTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
