<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\BeverageOrder\Pages;
use App\Filament\Tenant\Resources\BeverageOrderResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordBeverageOrder extends CreateRecord {
    protected static string $resource = BeverageOrderResource::class;
}
