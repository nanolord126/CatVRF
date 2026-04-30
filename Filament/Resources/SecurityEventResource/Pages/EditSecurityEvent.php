<?php

declare(strict_types=1);

namespace App\Filament\Resources\SecurityEventResource\Pages;

use App\Filament\Resources\SecurityEventResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSecurityEvent extends EditRecord
{
    protected static string $resource = SecurityEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\Action::make('resolve')
                ->label('Resolve Event')
                ->icon('heroicon-o-check')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => ! $this->record->resolved)
                ->action(function () {
                    $this->record->update([
                        'resolved' => true,
                        'resolved_at' => now(),
                        'resolved_by' => auth()->id(),
                    ]);
                }),
        ];
    }
}
