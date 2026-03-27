<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\EventPlanning\EventResource\Pages;

use App\Filament\Tenant\Resources\EventPlanning\EventResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

/**
 * Filament Resource ListEvents.
 * Канон 2026: Tenant Scoping + Audit Logging + Header Actions.
 */
final class ListEvents extends ListRecords
{
    protected static string $resource = EventResource::class;

    /**
     * Header Actions — Кнопки действий над списком.
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Создать Праздник')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
