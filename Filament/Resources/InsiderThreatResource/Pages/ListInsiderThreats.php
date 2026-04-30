<?php

declare(strict_types=1);

namespace App\Filament\Resources\InsiderThreatResource\Pages;

use App\Filament\Resources\InsiderThreatResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListInsiderThreats extends ListRecords
{
    protected static string $resource = InsiderThreatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
