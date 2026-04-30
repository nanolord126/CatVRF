<?php

declare(strict_types=1);

namespace App\Filament\Resources\InsiderThreatLogResource\Pages;

use App\Filament\Resources\InsiderThreatLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListInsiderThreatLogs extends ListRecords
{
    protected static string $resource = InsiderThreatLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->action('export'),
        ];
    }
}
