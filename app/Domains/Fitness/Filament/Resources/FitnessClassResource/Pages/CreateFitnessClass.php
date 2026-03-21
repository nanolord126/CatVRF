<?php declare(strict_types=1);

namespace App\Domains\Fitness\Filament\Resources\FitnessClassResource\Pages;

use App\Domains\Fitness\Filament\Resources\FitnessClassResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateFitnessClass extends CreateRecord
{
    protected static string $resource = FitnessClassResource::class;
}
