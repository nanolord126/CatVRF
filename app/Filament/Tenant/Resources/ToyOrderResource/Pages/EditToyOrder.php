<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ToyOrderResource;

use App\Filament\Tenant\Resources\ToyOrderResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;
use Illuminate\Support\Facades\Log;

class EditToyOrder extends EditRecord
{
    protected static string $resource = \App\Filament\Tenant\Resources\ToyOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        Log::channel('audit')->info('Toy Order Updated (Filament UI)', [
            'id' => $this->record->id,
            'status' => $this->record->status
        ]);
    }
}
