<?php

declare(strict_types=1);

namespace App\Filament\Resources\CooldownPeriodResource\Pages;

use App\Filament\Resources\CooldownPeriodResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListCooldownPeriods extends ListRecords
{
    protected static string $resource = CooldownPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('mark_expired')
                ->label('Mark Expired Cooldowns')
                ->icon('heroicon-o-check-circle')
                ->action(function () {
                    $count = app(\App\Services\Security\CooldownService::class)
                        ->markExpiredCooldowns();
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Expired Cooldowns Marked')
                        ->body("{$count} cooldown(s) marked as expired.")
                        ->success()
                        ->send();
                })
                ->requiresConfirmation(),
        ];
    }
}
