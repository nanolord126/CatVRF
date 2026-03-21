<?php declare(strict_types=1);

namespace App\Domains\Entertainment\Filament\Resources\EntertainmentEventResource\Pages;

use App\Domains\Entertainment\Filament\Resources\EntertainmentEventResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateEntertainmentEvent extends CreateRecord
{
    protected static string $resource = EntertainmentEventResource::class;
}
