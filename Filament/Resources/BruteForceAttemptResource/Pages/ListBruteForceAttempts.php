<?php

declare(strict_types=1);

namespace App\Filament\Resources\BruteForceAttemptResource\Pages;

use App\Filament\Resources\BruteForceAttemptResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListBruteForceAttempts extends ListRecords
{
    protected static string $resource = BruteForceAttemptResource::class;

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
