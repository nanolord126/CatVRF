<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\VIPBooking\Pages;
use App\Filament\Tenant\Resources\VIPBookingResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordVIPBooking extends EditRecord {
    protected static string $resource = VIPBookingResource::class;
}
