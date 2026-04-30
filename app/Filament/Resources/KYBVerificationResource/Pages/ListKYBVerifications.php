<?php

declare(strict_types=1);

namespace App\Filament\Resources\KYBVerificationResource\Pages;

use App\Filament\Resources\KYBVerificationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListKYBVerifications extends ListRecords
{
    protected static string $resource = KYBVerificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
