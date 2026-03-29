<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\BeverageOrder\Pages;
use App\Filament\Tenant\Resources\BeverageOrderResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordBeverageOrder extends EditRecord {
    protected static string $resource = BeverageOrderResource::class;
}
