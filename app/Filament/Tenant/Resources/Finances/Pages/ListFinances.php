<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Finances\Pages;
use App\Filament\Tenant\Resources\FinancesResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsFinances extends ListRecords {
    protected static string $resource = FinancesResource::class;
}
