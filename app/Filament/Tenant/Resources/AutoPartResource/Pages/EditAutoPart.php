<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\AutoPartResource\Pages;

use App\Filament\Tenant\Resources\AutoPartResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditAutoPart extends EditRecord
{
    protected static string $resource = AutoPartResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        activity()
            ->performedBy(auth()->user())
            ->on($this->record)
            ->withProperty('correlation_id', $this->record->correlation_id)
            ->log('Auto part inventory updated');
    }
}
