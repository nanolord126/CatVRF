<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\LuxuryProductResource\Pages;

use App\Filament\Tenant\Resources\LuxuryProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * ListLuxuryProducts
 * 
 * Layer 1-3: Filament Pages
 * Список эксклюзивных товаров с tenant scoping.
 * 
 * @version 1.0.0
 * @author CatVRF
 */
final class ListLuxuryProducts extends ListRecords
{
    protected static string $resource = LuxuryProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Добавить эксклюзив')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
