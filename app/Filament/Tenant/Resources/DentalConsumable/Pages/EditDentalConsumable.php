<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\DentalConsumable\Pages;
use App\Filament\Tenant\Resources\DentalConsumableResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordDentalConsumable extends EditRecord {
    protected static string $resource = DentalConsumableResource::class;
}
