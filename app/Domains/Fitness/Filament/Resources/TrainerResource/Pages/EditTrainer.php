<?php declare(strict_types=1);

namespace App\Domains\Fitness\Filament\Resources\TrainerResource\Pages;

use App\Domains\Fitness\Filament\Resources\TrainerResource;
use Filament\Resources\Pages\EditRecord;

final class EditTrainer extends EditRecord
{
    protected static string $resource = TrainerResource::class;
}
