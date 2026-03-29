<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Dentist\Pages;
use App\Filament\Tenant\Resources\DentistResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordDentist extends EditRecord {
    protected static string $resource = DentistResource::class;
}
