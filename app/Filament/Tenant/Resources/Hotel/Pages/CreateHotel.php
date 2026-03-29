<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Hotel\Pages;
use App\Filament\Tenant\Resources\HotelResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordHotel extends CreateRecord {
    protected static string $resource = HotelResource::class;
}
