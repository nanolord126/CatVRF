<?php declare(strict_types=1);

namespace App\Domains\Fitness\Filament\Resources\FitnessClassResource\Pages;

use App\Domains\Fitness\Filament\Resources\FitnessClassResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListFitnessClasses extends ListRecords
{
    protected static string $resource = FitnessClassResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
