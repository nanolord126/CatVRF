<?php declare(strict_types=1);

namespace App\Domains\Fitness\Filament\Resources\GymResource\Pages;

use App\Domains\Fitness\Filament\Resources\GymResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateGym extends CreateRecord
{
    protected static string $resource = GymResource::class;
}
