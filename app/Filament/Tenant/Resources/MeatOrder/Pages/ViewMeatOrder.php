<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\MeatOrder\Pages;
use App\Filament\Tenant\Resources\MeatOrderResource;
use Filament\Resources\Pages\ViewRecord;
final class ViewRecordMeatOrder extends ViewRecord {
    protected static string $resource = MeatOrderResource::class;
}
