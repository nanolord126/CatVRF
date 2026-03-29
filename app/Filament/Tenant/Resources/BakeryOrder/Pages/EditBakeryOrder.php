<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\BakeryOrder\Pages;
use App\Filament\Tenant\Resources\BakeryOrderResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordBakeryOrder extends EditRecord {
    protected static string $resource = BakeryOrderResource::class;
}
