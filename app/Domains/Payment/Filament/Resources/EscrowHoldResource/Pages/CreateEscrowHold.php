<?php

declare(strict_types=1);

namespace App\Domains\Payment\Filament\Resources\EscrowHoldResource\Pages;

use App\Domains\Payment\Filament\Resources\EscrowHoldResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateEscrowHold extends CreateRecord
{
    protected static string $resource = EscrowHoldResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
