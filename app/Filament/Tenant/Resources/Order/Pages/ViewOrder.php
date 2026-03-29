<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Order\Pages;
use App\Filament\Tenant\Resources\OrderResource;
use Filament\Resources\Pages\ViewRecord;
final class ViewRecordOrder extends ViewRecord {
    protected static string $resource = OrderResource::class;
}
