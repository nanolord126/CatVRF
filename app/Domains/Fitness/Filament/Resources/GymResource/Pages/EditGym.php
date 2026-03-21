<?php declare(strict_types=1);

namespace App\Domains\Fitness\Filament\Resources\GymResource\Pages;

use App\Domains\Fitness\Filament\Resources\GymResource;
use Filament\Resources\Pages\EditRecord;

final class EditGym extends EditRecord
{
    protected static string $resource = GymResource::class;
}
