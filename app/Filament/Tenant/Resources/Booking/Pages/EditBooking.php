<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Booking\Pages;
use App\Filament\Tenant\Resources\BookingResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordBooking extends EditRecord {
    protected static string $resource = BookingResource::class;
}
