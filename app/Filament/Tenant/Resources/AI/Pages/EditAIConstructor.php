<?php

namespace App\Filament\Tenant\Resources\AI\Pages;

use App\Filament\Tenant\Resources\AI\AIConstructorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAIConstructor extends EditRecord
{
    protected static string $resource = AIConstructorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

