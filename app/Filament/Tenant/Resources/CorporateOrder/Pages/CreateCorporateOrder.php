<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\CorporateOrder\Pages;
use App\Filament\Tenant\Resources\CorporateOrderResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordCorporateOrder extends CreateRecord {
    protected static string $resource = CorporateOrderResource::class;
}
