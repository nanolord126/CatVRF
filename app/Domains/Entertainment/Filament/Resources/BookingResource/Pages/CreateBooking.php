<?php declare(strict_types=1);

namespace App\Domains\Entertainment\Filament\Resources\BookingResource\Pages;

use App\Domains\Entertainment\Filament\Resources\BookingResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;
}
