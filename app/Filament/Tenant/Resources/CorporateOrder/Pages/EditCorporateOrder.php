<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\CorporateOrder\Pages;
use App\Filament\Tenant\Resources\CorporateOrderResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordCorporateOrder extends EditRecord {
    protected static string $resource = CorporateOrderResource::class;
}
