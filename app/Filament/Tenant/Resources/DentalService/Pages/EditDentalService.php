<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\DentalService\Pages;
use App\Filament\Tenant\Resources\DentalServiceResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordDentalService extends EditRecord {
    protected static string $resource = DentalServiceResource::class;
}
