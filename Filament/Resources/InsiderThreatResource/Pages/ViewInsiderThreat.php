<?php

declare(strict_types=1);

namespace App\Filament\Resources\InsiderThreatResource\Pages;

use App\Filament\Resources\InsiderThreatResource;
use App\Models\InsiderThreatLog;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

final class ViewInsiderThreat extends ViewRecord
{
    protected static string $resource = InsiderThreatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('mark_reviewed')
                ->label('Mark as Reviewed')
                ->icon('heroicon-o-check')
                ->requiresConfirmation()
                ->action(function () {
                    /** @var InsiderThreatLog $record */
                    $record = $this->record;
                    $record->markAsReviewed(Auth::id(), 'Reviewed via dashboard');
                })
                ->visible(fn () => $this->record->requires_review && ! $this->record->is_reviewed),
        ];
    }
}
