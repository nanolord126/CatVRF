<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Logistics\Pages;
use App\Filament\Tenant\Resources\LogisticsResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordLogistics extends CreateRecord {
    protected static string $resource = LogisticsResource::class;
}
