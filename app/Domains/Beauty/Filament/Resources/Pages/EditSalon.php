<?php declare(strict_types=1);

namespace App\Domains\Beauty\Filament\Resources\Pages;

use App\Domains\Beauty\Filament\Resources\SalonResource;
use Filament\Resources\Pages\EditRecord;

final class EditSalon extends EditRecord
{
    protected static string $resource = SalonResource::class;
}
