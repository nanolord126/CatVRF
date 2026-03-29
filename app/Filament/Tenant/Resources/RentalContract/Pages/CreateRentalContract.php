<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\RentalContract\Pages;
use App\Filament\Tenant\Resources\RentalContractResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordRentalContract extends CreateRecord {
    protected static string $resource = RentalContractResource::class;
}
