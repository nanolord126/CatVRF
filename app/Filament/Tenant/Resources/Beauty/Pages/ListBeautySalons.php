<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\Pages;

use App\Filament\Tenant\Resources\Beauty\BeautySalonResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

final class ListBeautySalons extends ListRecords
{
    protected static string $resource = BeautySalonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Добавить салон'),
        ];
    }
}
