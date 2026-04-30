<?php

declare(strict_types=1);

namespace App\Domains\Payment\Filament\Resources\EscrowHoldResource\Pages;

use App\Domains\Payment\Filament\Resources\EscrowHoldResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewEscrowHold extends ViewRecord
{
    protected static string $resource = EscrowHoldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
