<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\TaxiDriver\Pages;
use App\Filament\Tenant\Resources\TaxiDriverResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordTaxiDriver extends CreateRecord {
    protected static string $resource = TaxiDriverResource::class;
}
