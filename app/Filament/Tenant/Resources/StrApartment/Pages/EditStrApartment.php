<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\StrApartment\Pages;
use App\Filament\Tenant\Resources\StrApartmentResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordStrApartment extends EditRecord {
    protected static string $resource = StrApartmentResource::class;
}
