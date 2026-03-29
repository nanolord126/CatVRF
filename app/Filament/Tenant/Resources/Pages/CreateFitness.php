<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Fitness\Pages;

use use App\Filament\Tenant\Resources\FitnessResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateFitness extends CreateRecord
{
    protected static string $resource = FitnessResource::class;

    public function getTitle(): string
    {
        return 'Create Fitness';
    }
}