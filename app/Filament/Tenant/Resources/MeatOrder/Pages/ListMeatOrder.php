<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\MeatOrder\Pages;
use App\Filament\Tenant\Resources\MeatOrderResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsMeatOrder extends ListRecords {
    protected static string $resource = MeatOrderResource::class;
}
