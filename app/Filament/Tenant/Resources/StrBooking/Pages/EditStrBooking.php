<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\StrBooking\Pages;
use App\Filament\Tenant\Resources\StrBookingResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordStrBooking extends EditRecord {
    protected static string $resource = StrBookingResource::class;
}
