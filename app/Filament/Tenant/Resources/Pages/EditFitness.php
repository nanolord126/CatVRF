<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Fitness\Pages;

use use App\Filament\Tenant\Resources\FitnessResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditFitness extends EditRecord
{
    protected static string $resource = FitnessResource::class;

    public function getTitle(): string
    {
        return 'Edit Fitness';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}