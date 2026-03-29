<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\StrApartment\Pages;
use App\Filament\Tenant\Resources\StrApartmentResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordStrApartment extends CreateRecord {
    protected static string $resource = StrApartmentResource::class;
}
