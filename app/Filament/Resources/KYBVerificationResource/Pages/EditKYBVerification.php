<?php

declare(strict_types=1);

namespace App\Filament\Resources\KYBVerificationResource\Pages;

use App\Filament\Resources\KYBVerificationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditKYBVerification extends EditRecord
{
    protected static string $resource = KYBVerificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
