<?php declare(strict_types=1);

namespace App\Domains\Fitness\Filament\Resources\TrainerResource\Pages;

use App\Domains\Fitness\Filament\Resources\TrainerResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateTrainer extends CreateRecord
{
    protected static string $resource = TrainerResource::class;
}
