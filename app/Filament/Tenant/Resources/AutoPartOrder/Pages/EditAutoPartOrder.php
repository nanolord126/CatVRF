<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\AutoPartOrder\Pages;
use App\Filament\Tenant\Resources\AutoPartOrderResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordAutoPartOrder extends EditRecord {
    protected static string $resource = AutoPartOrderResource::class;
}
