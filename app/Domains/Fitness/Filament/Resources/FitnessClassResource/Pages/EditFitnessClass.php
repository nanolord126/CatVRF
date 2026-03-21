<?php declare(strict_types=1);

namespace App\Domains\Fitness\Filament\Resources\FitnessClassResource\Pages;

use App\Domains\Fitness\Filament\Resources\FitnessClassResource;
use Filament\Resources\Pages\EditRecord;

final class EditFitnessClass extends EditRecord
{
    protected static string $resource = FitnessClassResource::class;
}
