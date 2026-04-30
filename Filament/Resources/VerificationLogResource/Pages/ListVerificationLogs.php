<?php

declare(strict_types=1);

namespace App\Filament\Resources\VerificationLogResource\Pages;

use App\Filament\Resources\VerificationLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListVerificationLogs extends ListRecords
{
    protected static string $resource = VerificationLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
