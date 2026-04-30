<?php

declare(strict_types=1);

namespace App\Filament\Resources\KYBVerificationResource\Pages;

use App\Filament\Resources\KYBVerificationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

final class ViewKYBVerification extends ViewRecord
{
    protected static string $resource = KYBVerificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
