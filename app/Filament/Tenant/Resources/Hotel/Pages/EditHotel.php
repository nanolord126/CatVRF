<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Hotel\Pages;
use App\Filament\Tenant\Resources\HotelResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordHotel extends EditRecord {
    protected static string $resource = HotelResource::class;
}
