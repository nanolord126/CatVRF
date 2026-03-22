<?php declare(strict_types=1);

namespace App\Domains\Fitness\Filament\Resources\TrainerResource\Pages;

use App\Domains\Fitness\Filament\Resources\TrainerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListTrainers extends ListRecords
{
    protected static string $resource = TrainerResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
