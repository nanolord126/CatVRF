<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Dentist\Pages;
use App\Filament\Tenant\Resources\DentistResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordDentist extends CreateRecord {
    protected static string $resource = DentistResource::class;
}
