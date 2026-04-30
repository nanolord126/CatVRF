<?php

declare(strict_types=1);

namespace App\Filament\Resources\InsiderThreatLogResource\Pages;

use App\Filament\Resources\InsiderThreatLogResource;
use App\Models\InsiderThreatLog;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms\Components\Textarea;

final class ViewInsiderThreatLog extends ViewRecord
{
    protected static string $resource = InsiderThreatLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('review')
                ->label('Mark as Reviewed')
                ->icon('heroicon-o-check')
                ->visible(fn (InsiderThreatLog $record): bool => $record->requires_review && ! $record->is_reviewed)
                ->form([
                    Textarea::make('notes')
                        ->label('Review Notes')
                        ->rows(3),
                ])
                ->action(function (InsiderThreatLog $record, array $data): void {
                    $record->markAsReviewed(auth()->id(), $data['notes'] ?? null);
                }),
        ];
    }
}
