<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Fitness\Pages;

use use App\Filament\Tenant\Resources\FitnessResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewFitness extends ViewRecord
{
    protected static string $resource = FitnessResource::class;

    public function getTitle(): string
    {
        return 'View Fitness';
    }
}