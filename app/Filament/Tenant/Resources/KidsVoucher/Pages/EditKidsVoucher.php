<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\KidsVoucher\Pages;
use App\Filament\Tenant\Resources\KidsVoucherResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordKidsVoucher extends EditRecord {
    protected static string $resource = KidsVoucherResource::class;
}
