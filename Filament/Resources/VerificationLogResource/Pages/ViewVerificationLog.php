<?php

declare(strict_types=1);

namespace App\Filament\Resources\VerificationLogResource\Pages;

use App\Filament\Resources\VerificationLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

final class ViewVerificationLog extends ViewRecord
{
    protected static string $resource = VerificationLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
