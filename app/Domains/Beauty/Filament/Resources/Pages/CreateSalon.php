<?php declare(strict_types=1);

namespace App\Domains\Beauty\Filament\Resources\Pages;

use App\Domains\Beauty\Filament\Resources\SalonResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateSalon extends CreateRecord
{
    protected static string $resource = SalonResource::class;
}
